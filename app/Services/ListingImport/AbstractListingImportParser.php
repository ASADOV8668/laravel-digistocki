<?php

namespace App\Services\ListingImport;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\ListingImportTerm;
use App\Models\PhoneModel;
use App\Support\PersianDigits;
use App\Support\PersianSearch;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AbstractListingImportParser implements ListingImportParser
{
    /** @var array<string, int|null> */
    private array $attributeIds = [];

    /** @var array<string, array<string, mixed>> */
    private array $brandCache = [];

    /** @var array<int, PhoneModel> */
    private array $modelCache = [];

    private ?Collection $terms = null;

    private array $termAttributes = [];

    protected const COLORS = [
        '💙' => 'آبی', '🩵' => 'آبی', '🔵' => 'آبی', '💚' => 'سبز', '🟢' => 'سبز',
        '🩷' => 'صورتی', '🩷️' => 'صورتی', '💗' => 'صورتی',
        '🖤' => 'مشکی', '⚫' => 'مشکی', '⚫️' => 'مشکی',
        '🤍' => 'سفید', '⚪' => 'سفید', '⚪️' => 'سفید',
        '💛' => 'زرد', '🟡' => 'زرد', '💜' => 'بنفش', '🟣' => 'بنفش',
        '🍊' => 'نارنجی', '🟤' => 'قهوه‌ای', '🩶' => 'خاکستری',
        '🍒' => 'قرمز', '🔴' => 'قرمز', '❤️' => 'قرمز',
    ];

    protected function clean(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = PersianDigits::toEnglish($text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return trim(preg_replace('/[ \t]+/u', ' ', $text) ?? $text);
    }

    /** @return array<int, string> */
    protected function lines(string $text): array
    {
        return array_values(array_filter(array_map(fn (string $line): string => trim($this->clean($line)), explode("\n", $text)), fn (string $line): bool => $line !== ''));
    }

    protected function dateFromText(string $text, ?string $defaultDate): ?string
    {
        if (preg_match('/(?<!\d)(14\d{2})[\/-](\d{1,2})[\/-](\d{1,2})(?!\d)/u', $this->clean($text), $match)) {
            try {
                return Verta::parseFormat('Y/m/d', sprintf('%04d/%02d/%02d', $match[1], $match[2], $match[3]))->datetime()->format('Y-m-d');
            } catch (\Throwable) {
                // The import preview will ask for a date when the source date is invalid.
            }
        }

        if ($defaultDate && Carbon::hasFormat($defaultDate, 'Y-m-d')) {
            return $defaultDate;
        }

        return null;
    }

    /** @return array<string, mixed> */
    protected function row(string $raw, string $modelText, ?string $color, ?int $price, ?int $battery, array $extra = []): array
    {
        $model = $this->resolveModel($modelText);
        $attributes = [];
        $issues = [];
        $colorCandidatesMatched = false;

        if ($model) {
            if ($color !== null && ($id = $this->attributeId('color'))) {
                $attributes[$id] = $color;
            }
            if (($storage = $extra['storage'] ?? $this->storage($modelText)) !== null && ($id = $this->attributeId('storage'))) {
                $attributes[$id] = $storage;
            }
            if ($battery !== null && ($id = $this->attributeId('battery_health'))) {
                $attributes[$id] = $battery;
            }
            if (isset($extra['registered']) && ($id = $this->attributeId('registered'))) {
                $attributes[$id] = $extra['registered'] ? 1 : 0;
            }
            if (! empty($extra['registration_status']) && ($id = $this->attributeId('registration_status'))) {
                $attributes[$id] = $extra['registration_status'];
            }
            if (isset($extra['activation_status']) && ($id = $this->attributeId('activation_status'))) {
                $attributes[$id] = $extra['activation_status'] === 'Active' ? 'اکتیو' : 'نات اکتیو';
            }

            $termText = $raw."\n".$modelText;
            $attributes = array_replace($attributes, $this->termValues($model, $termText));
            if ($id = $this->attributeId('color')) {
                $color = $attributes[$id] ?? $color;
                $candidates = $this->colorCandidates($termText);
                $colorAttribute = ($this->termAttributes[$model->id] ?? null)?->get($id);
                if ($candidates !== [] && $colorAttribute) {
                    $colorCandidatesMatched = true;
                    $color = (new ColorCandidateMatcher)->match($termText, $candidates, $colorAttribute->optionsForModel($model->id));
                    if ($color === null) {
                        unset($attributes[$id]);
                    } else {
                        $attributes[$id] = $color;
                    }
                }
            }
        }

        $supported = $model !== null && in_array($this->brandKey($model->brand_id), ['apple', 'samsung', 'xiaomi'], true);
        if (! $model) {
            $issues[] = 'مدل گوشی در کاتالوگ پیدا نشد.';
        } elseif (! $supported) {
            $issues[] = 'این محصول فعلاً فقط برای گوشی‌های اپل، سامسونگ و شیائومی قابل وارد کردن است.';
        }
        if ($price === null) {
            $issues[] = 'قیمت از متن تشخیص داده نشد.';
        }
        if ($color === null && $model && $this->attributeId('color')) {
            $issues[] = $colorCandidatesMatched
                ? 'رنگ این ایموجی برای این مدل قطعی نیست؛ لطفاً رنگ را انتخاب کنید.'
                : 'رنگ تشخیص داده نشد؛ لطفاً در پیش‌نمایش انتخاب کنید.';
        }

        $description = trim((string) ($extra['description'] ?? ''));
        if ($extra['part_number'] ?? null) {
            $description = trim('پارت نامبر: '.$extra['part_number'].' '.$description);
        }
        if ($extra['cycle'] ?? null) {
            $description = trim('Cycle: '.$extra['cycle'].' '.$description);
        }

        $row = [
            'raw' => trim($raw),
            'supported' => $supported && $price !== null,
            'brand_id' => $model?->brand_id,
            'phone_model_id' => $model?->id,
            'brand_name' => $model?->brand?->name_en ?: $model?->brand?->name,
            'model_name' => $model?->name_en ?: $model?->name,
            'attributes' => $attributes,
            'color' => $color,
            'color_candidates_matched' => $colorCandidatesMatched,
            'price' => $price,
            'price_on_request' => false,
            'description' => $description,
            'issues' => array_values(array_unique($issues)),
            'unsupported' => ! $supported,
            'part_number' => $extra['part_number'] ?? null,
            'activation_status' => $extra['activation_status'] ?? null,
            'registered' => $extra['registered'] ?? null,
            'cycle' => $extra['cycle'] ?? null,
        ];
        $row['dedupe_key'] = $this->dedupeKey($row);

        return $row;
    }

    private function termValues(PhoneModel $model, string $text): array
    {
        $this->terms ??= ListingImportTerm::query()
            ->where(fn ($query) => $query->whereNotNull('attribute_id')->whereNotNull('attribute_value'))
            ->orWhereNotNull('color_candidates')->orderBy('id')->get()
            ->sortByDesc(fn (ListingImportTerm $term): int => mb_strlen($term->emoji));

        if ($this->terms->isEmpty()) {
            return [];
        }

        $this->termAttributes[$model->id] ??= Attribute::query()->where('is_active', true)
            ->visibleForModel($model->id)->with('optionRestrictions')
            ->where(fn ($query) => $query->where('slug', 'color')
                ->orWhereIn('id', $this->terms->pluck('attribute_id')->filter()->unique()))
            ->get()->keyBy('id');

        $text = $this->normalizeTermText($text);
        $values = [];
        foreach ($this->terms as $term) {
            $attribute = $this->termAttributes[$model->id]->get($term->attribute_id);
            if (! $attribute || ! $this->containsTerm($text, $this->normalizeTermText($term->emoji))
                || ! in_array($term->attribute_value, $attribute->optionsForModel($model->id), true)) {
                continue;
            }

            if ($attribute->type === AttributeType::MultiSelect) {
                if (! in_array($term->attribute_value, $values[$attribute->id] ?? [], true)) {
                    $values[$attribute->id][] = $term->attribute_value;
                }
            } else {
                $values[$attribute->id] ??= $attribute->type === AttributeType::Integer
                    ? (int) $term->attribute_value
                    : $term->attribute_value;
            }
        }

        return $values;
    }

    private function colorCandidates(string $text): array
    {
        $normalize = fn (string $value): string => str_replace(["\u{FE0E}", "\u{FE0F}"], '', $this->normalizeTermText($value));
        $text = $normalize($text);

        return $this->terms->filter(fn (ListingImportTerm $term): bool => $term->color_candidates
            && $this->containsTerm($text, $normalize($term->emoji)))
            ->flatMap(fn (ListingImportTerm $term): array => $term->color_candidates)->unique()->values()->all();
    }

    private function normalizeTermText(string $text): string
    {
        $text = mb_strtolower(PersianSearch::normalize($this->clean($text)));

        return preg_replace('/[\h\x{200C}]+/u', ' ', $text) ?? $text;
    }

    private function containsTerm(string $text, string $term): bool
    {
        if ($term === '') {
            return false;
        }

        $word = '/[\p{L}\p{N}\p{M}_\x{200C}]/u';
        $startsWithWord = preg_match($word, mb_substr($term, 0, 1));
        $endsWithWord = preg_match($word, mb_substr($term, -1));
        $length = mb_strlen($term);
        $offset = 0;

        while (($start = mb_strpos($text, $term, $offset)) !== false) {
            $end = $start + $length;
            if ((! $startsWithWord || $start === 0 || ! preg_match($word, mb_substr($text, $start - 1, 1)))
                && (! $endsWithWord || ! preg_match($word, mb_substr($text, $end, 1)))) {
                return true;
            }
            $offset = $start + 1;
        }

        return false;
    }

    protected function price(?string $value): ?int
    {
        if (! $value) {
            return null;
        }
        $value = PersianDigits::toEnglish($value);
        $value = str_replace(['٬', '،', ',', '.', '/', ' ', '💰', '💵', 'تومان', 'm', 'M'], '', $value);
        preg_match('/\d+(?:\.\d+)?/', $value, $match);
        if (! isset($match[0])) {
            return null;
        }

        return (int) round(((float) $match[0]) * 1000);
    }

    protected function color(string $value): ?string
    {
        foreach (self::COLORS as $symbol => $label) {
            if (str_contains($value, $symbol)) {
                return $label;
            }
        }

        $map = ['blue' => 'آبی', 'black' => 'مشکی', 'white' => 'سفید', 'green' => 'سبز', 'pink' => 'صورتی', 'orange' => 'نارنجی', 'purple' => 'بنفش', 'yellow' => 'زرد', 'red' => 'قرمز', 'gray' => 'خاکستری', 'grey' => 'خاکستری', 'brown' => 'قهوه‌ای'];
        foreach ($map as $needle => $label) {
            if (stripos($value, $needle) !== false) {
                return $label;
            }
        }

        return null;
    }

    protected function battery(string $value): ?int
    {
        return preg_match('/(?<!\d)(\d{1,3})\s*(?:%|bt)\b/iu', PersianDigits::toEnglish($value), $match) ? (int) $match[1] : null;
    }

    protected function storage(string $value): ?int
    {
        if (! preg_match('/(?<!\d)(\d{1,4})\s*(tb|t|g|gb)\b/iu', PersianDigits::toEnglish($value), $match)) {
            return null;
        }

        return in_array(strtolower($match[2]), ['tb', 't'], true) ? (int) $match[1] * 1024 : (int) $match[1];
    }

    /** @return array<int, array<string, mixed>> */
    protected function unique(array $items): array
    {
        $seen = [];
        $result = [];
        foreach ($items as $item) {
            $key = (string) ($item['dedupe_key'] ?? md5(serialize($item)));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $item;
        }

        return array_values($result);
    }

    /** @return array{items: array<int, array<string, mixed>>, detected_date: ?string, date_required: bool} */
    protected function result(array $items, string $text, ?string $defaultDate): array
    {
        $date = $this->dateFromText($text, $defaultDate);

        return ['items' => $this->unique($items), 'detected_date' => $date, 'date_required' => $date === null];
    }

    protected function resolveModel(string $text): ?PhoneModel
    {
        $value = Str::lower($this->clean($text));
        $brand = $this->resolveBrand($value);
        if (! $brand) {
            return null;
        }
        $models = PhoneModel::query()->where('brand_id', $brand->id)->where('is_active', true)->get();
        $best = null;
        $score = 0;
        foreach ($models as $model) {
            $names = [$model->name, $model->name_en, $model->name_fa, $model->slug];
            foreach (array_filter($names) as $name) {
                $candidate = Str::lower(preg_replace('/[^\pL\pN]+/u', '', PersianDigits::toEnglish((string) $name)) ?? '');
                $haystack = Str::lower(preg_replace('/[^\pL\pN]+/u', '', PersianDigits::toEnglish($value)) ?? '');
                if ($candidate !== '' && str_contains($haystack, $candidate) && mb_strlen($candidate) > $score) {
                    $best = $model;
                    $score = mb_strlen($candidate);
                }
            }
        }
        if ($best) {
            return $best;
        }

        $canonical = $this->canonicalIphone($value);
        if ($canonical) {
            foreach ($models as $model) {
                $modelName = Str::lower(implode(' ', array_filter([$model->name_en, $model->name, $model->name_fa])));
                if ($this->canonicalIphone($modelName) === $canonical) {
                    return $model;
                }
            }
        }

        return null;
    }

    protected function resolveBrand(string $value): ?Brand
    {
        $key = Str::lower($value);
        if (isset($this->brandCache[$key])) {
            return $this->brandCache[$key]['model'] ?? null;
        }
        $brand = Brand::query()->where('is_active', true)->get()->first(function (Brand $brand) use ($value): bool {
            $english = Str::lower((string) $brand->name_en);
            $name = Str::lower((string) $brand->name);

            return str_contains($value, $english) || str_contains($value, $name)
                || ($english === 'apple' && (str_contains($value, 'iphone') || preg_match('/\b(?:xs|max|se)\b/i', $value)))
                || ($english === 'samsung' && preg_match('/\b(?:s\d{1,2}|a\d{1,2}|galaxy)\b/i', $value))
                || ($english === 'xiaomi' && preg_match('/\b(?:redmi|poco|mi)\b/i', $value));
        });
        $this->brandCache[$key] = ['model' => $brand];

        return $brand;
    }

    private function canonicalIphone(string $value): ?string
    {
        if (! preg_match('/(?:iphone\s*)?(\d{1,2}|xs\s*max|se(?:\s*2020)?)\s*(pro\s*max|promax|pro|plus|mini)?/i', $value, $match)) {
            return null;
        }

        return preg_replace('/\s+/', '', strtolower(($match[1] ?? '').' '.($match[2] ?? ''))) ?: null;
    }

    private function attributeId(string $slug): ?int
    {
        if (! array_key_exists($slug, $this->attributeIds)) {
            $this->attributeIds[$slug] = Attribute::query()->where('slug', $slug)->value('id');
        }

        return $this->attributeIds[$slug] ? (int) $this->attributeIds[$slug] : null;
    }

    private function brandKey(int $brandId): string
    {
        return Str::lower((string) Brand::query()->whereKey($brandId)->value('name_en'));
    }

    /** @param array<string, mixed> $row */
    private function dedupeKey(array $row): string
    {
        return sha1(json_encode([
            $row['brand_id'], $row['phone_model_id'], $row['price'], $row['color'], $row['part_number'], $row['activation_status'], $row['registered'], $row['cycle'], $row['description'], $row['attributes'],
        ], JSON_UNESCAPED_UNICODE));
    }
}
