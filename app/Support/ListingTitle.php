<?php

namespace App\Support;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Services\SystemOptions;

final class ListingTitle
{
    public static function generate(?int $brandId, ?int $modelId, array $values, ?string $language = null, ?string $partNumber = null): ?string
    {
        $language ??= app(SystemOptions::class)->listingTitleLanguage();
        $english = $language === 'en';
        $brand = $brandId ? Brand::query()->find($brandId) : null;
        $model = $modelId ? PhoneModel::query()->find($modelId) : null;

        if (! $brand || ! $model || $model->brand_id !== $brand->id) {
            return null;
        }

        $attributes = Attribute::query()
            ->where('is_active', true)
            ->visibleForModel($model->id)
            ->whereIn('slug', ['color', 'storage', 'part_number', 'battery_health'])
            ->get()
            ->keyBy('slug');

        $parts = [
            $english ? ($brand->name_en ?: $brand->name) : $brand->name,
            $english ? ($model->name_en ?: $model->name) : PersianNumber::digits($model->name_fa ?: $model->name),
        ];

        $color = self::value($values, $attributes->get('color'));
        $storage = self::value($values, $attributes->get('storage'));
        $partNumber = self::value($values, $attributes->get('part_number')) ?? $partNumber;
        $battery = self::value($values, $attributes->get('battery_health'));
        if (filled($partNumber)) {
            $title = self::withPartNumber(implode(' ', $parts), $storage, $partNumber, $battery, english: $english);

            return mb_substr(trim($title.' '.($english ? self::englishAttributeValue('color', $color) : (string) $color)), 0, 160);
        }

        if (filled($color)) {
            $parts[] = $english ? self::englishAttributeValue('color', $color) : (string) $color;
        }

        if (filled($storage)) {
            $parts[] = $english
                ? self::englishDigits((string) $storage).' GB'
                : PersianNumber::digits((string) $storage).' گیگابایت';
        }

        return mb_substr(trim(implode(' ', array_filter($parts))), 0, 160);
    }

    public static function alignSubmittedTitle(string $title, array $values, array $previous = []): string
    {
        $fields = Attribute::query()->whereIn('slug', ['storage', 'part_number', 'battery_health'])->pluck('id', 'slug');
        $part = $values[$fields['part_number'] ?? 0] ?? null;
        if (! is_scalar($part) || ! filled($part)) {
            return $title;
        }

        return self::withPartNumber(
            $title, $values[$fields['storage'] ?? 0] ?? null, $part, $values[$fields['battery_health'] ?? 0] ?? null,
            previousPart: $previous[$fields['part_number'] ?? 0] ?? null,
        );
    }

    public static function withPartNumber(string $title, mixed $storage, mixed $part, mixed $battery, mixed $previousPart = null, ?bool $english = null): string
    {
        $part = is_scalar($part) ? strtoupper(trim((string) $part)) : '';
        if (! preg_match('/\A[A-Z0-9][A-Z0-9\/\-]{0,24}\z/iD', $part)) {
            return $title;
        }

        $storagePattern = '[0-9۰-۹٠-٩]{2,4}\h*(?:GB|گیگابایت|گیگ)';
        $batteryPattern = '[0-9۰-۹٠-٩]{1,3}\h*[%٪]';
        $storage = self::titleNumber($storage, $title, $storagePattern);
        $battery = self::titleNumber($battery, $title, $batteryPattern);
        $english ??= self::isEnglishTitle($title);
        $parts = [
            $storage === '' ? '' : ($english ? $storage.'GB' : PersianNumber::digits($storage).' گیگابایت'),
            $part,
            $battery === '' ? '' : ($english ? $battery.'%' : PersianNumber::digits($battery).'٪'),
        ];
        $cluster = implode(' ', array_filter($parts, fn (string $value): bool => $value !== ''));
        $codes = array_unique(array_filter([$part, $previousPart], fn ($code): bool => is_scalar($code) && filled($code)));
        $codePatterns = array_map(fn ($code): string => preg_quote((string) $code, '~'), $codes);
        $inserted = false;
        $aligned = preg_replace_callback('~(?<![\pL\pN])(?:'.$storagePattern.'|'.$batteryPattern.'|'.implode('|', $codePatterns).')(?![\pL\pN])~iu',
            function () use ($cluster, &$inserted): string {
                if ($inserted) {
                    return '';
                }
                $inserted = true;

                return $cluster;
            }, $title);

        $aligned = $inserted ? $aligned : rtrim(mb_substr($title, 0, 159 - mb_strlen($cluster))).' '.$cluster;

        return mb_substr(trim(preg_replace('/\h+/u', ' ', $aligned)), 0, 160);
    }

    private static function titleNumber(mixed $value, string $title, string $pattern): string
    {
        if (is_scalar($value) && preg_match('/\A\d{1,4}\z/', self::englishDigits(trim((string) $value)), $number)) {
            return $number[0];
        }

        return preg_match('~(?<![\pL\pN])('.$pattern.')(?![\pL\pN])~iu', $title, $match)
            ? (preg_replace('/\D/', '', self::englishDigits($match[1])) ?? '') : '';
    }

    public static function isEnglishTitle(?string $title): bool
    {
        return filled($title)
            && preg_match('/[A-Za-z]/', (string) $title) === 1
            && preg_match('/[\x{0600}-\x{06FF}]/u', (string) $title) !== 1;
    }

    public static function generateForCamera(int $brandId, int $modelId, array $values): ?string
    {
        $model = PhoneModel::query()->where('brand_id', $brandId)->where('is_active', true)->find($modelId);
        if (! $model) {
            return null;
        }

        $attributes = Attribute::query()->where('is_active', true)->visibleForModel($model->id)
            ->whereIn('slug', ['storage', 'part_number', 'battery_health', 'color'])->get()->keyBy('slug');
        $storage = self::englishDigits((string) self::value($values, $attributes->get('storage')));
        $part = strtoupper(trim((string) self::value($values, $attributes->get('part_number'))));
        $battery = self::englishDigits((string) self::value($values, $attributes->get('battery_health')));
        $color = self::englishAttributeValue('color', trim((string) self::value($values, $attributes->get('color'))));
        $modelName = preg_replace('/^(iPhone .+) Pro Max$/i', '$1 Promax', $model->name_en ?: $model->name);
        $parts = [
            $modelName,
            $storage === '' ? '' : $storage.'GB',
            $part,
            $battery === '' ? '' : $battery.'%',
            in_array(strtolower($color), ['deser titanium', 'desert titanium'], true) ? 'Desert' : $color,
        ];

        return mb_substr(implode(' ', array_filter($parts, fn (string $part): bool => $part !== '')), 0, 160);
    }

    public static function brandName(?Brand $brand, bool $english): string
    {
        if (! $brand) {
            return '';
        }

        return $english ? ($brand->name_en ?: $brand->name) : $brand->name;
    }

    public static function modelName(?PhoneModel $model, bool $english): string
    {
        if (! $model) {
            return '';
        }

        return $english ? ($model->name_en ?: $model->name) : ($model->name_fa ?: $model->name);
    }

    public static function attributeName(?string $slug, ?string $fallback, bool $english): string
    {
        if (! $english) {
            return (string) $fallback;
        }

        return [
            'storage' => 'Memory', 'ram' => 'RAM', 'color' => 'Color',
            'condition' => 'Condition', 'battery_health' => 'Battery health',
            'screen_condition' => 'Screen condition', 'body_condition' => 'Body condition',
            'water_damage' => 'Water damage', 'repaired' => 'Repaired',
            'repair_description' => 'Repair description', 'box_and_accessories' => 'Box and accessories',
            'charger_included' => 'Charger included', 'cable_included' => 'Cable included',
            'headphone_included' => 'Headphones included', 'warranty' => 'Warranty',
            'warranty_type' => 'Warranty type', 'warranty_remaining_months' => 'Warranty remaining',
            'original' => 'Original', 'registered' => 'Registered', 'registration_status' => 'Registration status',
            'invoice' => 'Purchase invoice', 'sim_type' => 'SIM type', 'network' => 'Network',
            'esim' => 'eSIM', 'screen_size' => 'Screen size', 'screen_type' => 'Screen type',
            'refresh_rate' => 'Refresh rate', 'main_camera_mp' => 'Main camera', 'camera_count' => 'Camera count',
            'selfie_camera_mp' => 'Selfie camera', 'battery_capacity' => 'Battery capacity',
            'fast_charging' => 'Fast charging', 'charging_power' => 'Charging power',
            'wireless_charging' => 'Wireless charging', 'chipset' => 'Chipset', 'os' => 'Operating system',
            'os_version' => 'Operating system version', 'fingerprint' => 'Fingerprint sensor',
            'face_recognition' => 'Face recognition', 'water_resistant' => 'Water resistant',
            'ip_rating' => 'Water resistance rating', 'headphone_jack' => 'Headphone jack',
            'nfc' => 'NFC', 'infrared' => 'Infrared', 'sale_condition' => 'Sale condition',
        ][$slug] ?? (string) $fallback;
    }

    public static function attributeValue(?string $slug, mixed $value, bool $english): string
    {
        if (is_array($value)) {
            $value = implode($english ? ', ' : '، ', array_filter($value, fn (mixed $item): bool => filled($item)));
        }

        return $english ? self::englishAttributeValue($slug, $value) : (string) $value;
    }

    public static function priceUnit(bool $english): string
    {
        return 'تومان';
    }

    private static function englishAttributeValue(?string $slug, mixed $value): string
    {
        $value = (string) $value;
        if ($slug === 'color') {
            return [
                'مشکی' => 'Black', 'سفید' => 'White', 'طلایی' => 'Gold', 'نقره‌ای' => 'Silver',
                'آبی' => 'Blue', 'سبز' => 'Green', 'بنفش' => 'Purple', 'خاکستری' => 'Gray',
                'قرمز' => 'Red', 'صورتی' => 'Pink', 'نارنجی' => 'Orange', 'سرمه‌ای' => 'Navy',
                'مسی' => 'Copper', 'تیفانی' => 'Tiffany', 'کرم' => 'Cream', 'زرد' => 'Yellow',
            ][$value] ?? $value;
        }

        return [
            'دارد' => 'Yes', 'ندارد' => 'No', 'سالم' => 'Good', 'در حد نو' => 'Like new',
            'نو' => 'New', 'کارکرده' => 'Used', 'نیاز به تعمیر' => 'Needs repair',
            'خط و خش جزئی' => 'Minor scratches', 'خط و خش عمیق' => 'Deep scratches',
            'شکسته' => 'Broken', 'تعویض شده' => 'Replaced', 'ضربه‌خورده' => 'Damaged',
            'رجیستر شده' => 'Registered', 'رجیستر نشده' => 'Not registered',
            'رجیستر شده به نام خودم' => 'Registered to me', 'رجیستر شده به نام دیگران' => 'Registered to another person',
            'نامشخص' => 'Unknown', 'تک‌سیم' => 'Single SIM', 'دو سیم' => 'Dual SIM',
            'گارانتی شرکتی' => 'Company warranty', 'گارانتی فروشنده' => 'Seller warranty',
            'گارانتی بین‌المللی' => 'International warranty', 'بدون گارانتی' => 'No warranty',
            'نقدی' => 'Cash', 'اقساطی' => 'Installment', 'معاوضه' => 'Exchange',
            'نقدی و اقساطی' => 'Cash and installment',
        ][$value] ?? self::englishDigits($value);
    }

    private static function englishDigits(string $value): string
    {
        return strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }

    private static function value(array $values, ?Attribute $attribute): mixed
    {
        if (! $attribute) {
            return null;
        }

        $value = $values[$attribute->id] ?? null;
        if (is_array($value)) {
            return implode('، ', array_filter($value, fn (mixed $item): bool => filled($item)));
        }

        return $value;
    }
}
