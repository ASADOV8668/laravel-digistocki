<?php

namespace App\Services\ListingImport;

final class CatalogColorMatcher
{
    public const MISSING_COLOR_ISSUE = 'رنگ تشخیص داده نشد؛ لطفاً در پیش‌نمایش انتخاب کنید.';

    private const SHADES = [
        ['🩵', ['آبی آسمانی', 'آبی روشن', 'آبی کم رنگ', 'sky blue', 'light blue', 'ice blue', 'آبی یخی']],
        [null, ['آبی اقیانوسی', 'ocean blue']],
        ['🍒', ['آلبالویی', 'قرمز آلبالویی', 'cherry', 'cherry red']],
    ];

    private const FAMILIES = [
        'آبی' => ['آبی', 'blue'],
        'قرمز' => ['قرمز', 'آلبالویی', 'red', 'cherry'],
        'مشکی' => ['مشکی', 'سیاه', 'black', 'midnight'],
        'سفید' => ['سفید', 'white', 'starlight'],
        'سبز' => ['سبز', 'green'],
        'صورتی' => ['صورتی', 'pink', 'rose'],
        'بنفش' => ['بنفش', 'purple', 'violet', 'lavender'],
        'زرد' => ['زرد', 'yellow'],
        'نارنجی' => ['نارنجی', 'orange', 'coral'],
        'خاکستری' => ['خاکستری', 'gray', 'grey', 'graphite'],
        'نقره‌ای' => ['نقره ای', 'silver'],
        'طلایی' => ['طلایی', 'gold'],
        'قهوه‌ای' => ['قهوه ای', 'brown'],
    ];

    public function match(string $raw, ?string $detected, array $options): ?string
    {
        if ($options === []) {
            return $detected;
        }

        $text = $this->normalize($raw);
        $labels = [];
        foreach ($options as $option) {
            $labels[$option] = $this->normalize($option);
        }

        $exact = array_filter($labels, fn ($label) => $this->contains($text, $label));
        if ($exact !== []) {
            // Keep the longer label when a generic option is part of a more specific one.
            $exact = array_filter($exact, fn ($label) => ! array_filter($exact, fn ($other) => $other !== $label && $this->contains($other, $label)));

            return count($exact) === 1 ? array_key_first($exact) : null;
        }

        $writtenShades = array_filter(self::SHADES, fn ($shade) => $this->hasAlias($text, $shade[1]));
        $symbolShades = array_filter(self::SHADES, fn ($shade) => $shade[0] !== null && str_contains($raw, $shade[0]));
        foreach ([$writtenShades, $symbolShades] as $shades) {
            if ($shades !== []) {
                $specific = array_filter($labels, fn ($label) => (bool) array_filter($shades, fn ($shade) => $this->hasAlias($label, $shade[1])));
                if ($specific !== []) {
                    return count($specific) === 1 ? array_key_first($specific) : null;
                }
            }
        }

        if ($detected === null) {
            $families = array_filter(self::FAMILIES, fn ($aliases) => $this->hasAlias($text, $aliases));
            if ($families === []) {
                return count($labels) === 1 ? array_key_first($labels) : null;
            }
            if (count($families) !== 1) {
                return null;
            }
            $detected = array_key_first($families);
        }
        $normalized = $this->normalize($detected);
        $sameLabel = array_keys($labels, $normalized, true);
        if ($sameLabel !== []) {
            return count($sameLabel) === 1 ? $sameLabel[0] : null;
        }
        $family = self::FAMILIES[$detected] ?? [];
        $matches = array_filter($labels, fn ($label) => $this->hasAlias($label, $family));

        return count($matches) === 1 ? array_key_first($matches) : null;
    }

    private function normalize(string $value): string
    {
        $value = strtr(mb_strtolower($value), ['آ' => 'ا', 'أ' => 'ا', 'ي' => 'ی', 'ك' => 'ک']);

        return trim(preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '');
    }

    private function contains(string $value, string $phrase): bool
    {
        return $phrase !== '' && str_contains(' '.$value.' ', ' '.$phrase.' ');
    }

    private function hasAlias(string $value, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if ($this->contains($value, $this->normalize($alias))) {
                return true;
            }
        }

        return false;
    }
}
