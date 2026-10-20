<?php

namespace App\Support;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Services\SystemOptions;

final class ListingTitle
{
    public static function generate(?int $brandId, ?int $modelId, array $values, ?string $language = null): ?string
    {
        $language ??= app(SystemOptions::class)->listingTitleLanguage();
        $english = $language === 'en';
        $brand = $brandId ? Brand::query()->find($brandId) : null;
        $model = $modelId ? PhoneModel::query()->find($modelId) : null;

        if (! $brand || ! $model || $model->brand_id !== $brand->id) {
            return null;
        }

        $attributes = $model->attributes()
            ->whereIn('slug', ['color', 'storage'])
            ->get()
            ->keyBy('slug');

        $parts = [
            $english ? ($brand->name_en ?: $brand->name) : $brand->name,
            $english ? ($model->name_en ?: $model->name) : PersianNumber::digits($model->name_fa ?: $model->name),
        ];

        $color = self::value($values, $attributes->get('color'));
        if (filled($color)) {
            $parts[] = $english ? self::englishAttributeValue('color', $color) : (string) $color;
        }

        $storage = self::value($values, $attributes->get('storage'));
        if (filled($storage)) {
            $parts[] = $english
                ? self::englishDigits((string) $storage).' GB'
                : PersianNumber::digits((string) $storage).' گیگابایت';
        }

        return mb_substr(trim(implode(' ', array_filter($parts))), 0, 160);
    }

    public static function isEnglishTitle(?string $title): bool
    {
        return filled($title)
            && preg_match('/[A-Za-z]/', (string) $title) === 1
            && preg_match('/[\x{0600}-\x{06FF}]/u', (string) $title) !== 1;
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
