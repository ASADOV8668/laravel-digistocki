<?php

namespace App\Support;

final class PersianSearch
{
    /** @return array<int, string> */
    public static function likes(string $term): array
    {
        $term = trim($term);
        $normalized = self::normalize($term);
        if (mb_strlen($normalized) < 2) {
            return [];
        }

        return collect(array_merge(self::variants($term), self::variants($normalized)))
            ->map(fn (string $variant): string => trim($variant))
            ->filter(fn (string $variant): bool => mb_strlen($variant) >= 2)
            ->unique()
            ->map(fn (string $variant): string => '%'.$variant.'%')
            ->values()
            ->all();
    }

    public static function normalize(string $term): string
    {
        return trim(strtr($term, [
            'آ' => 'ا', 'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ۀ' => 'ه', 'ة' => 'ه',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));
    }

    /** @return array<int, string> */
    private static function variants(string $term): array
    {
        $choices = [
            'ا' => ['ا', 'آ'], 'آ' => ['ا', 'آ'],
            'گ' => ['گ', 'ک', 'ك'], 'ک' => ['ک', 'گ', 'ك'], 'ك' => ['ک', 'گ', 'ك'],
            'ی' => ['ی', 'ي', 'ى'], 'ي' => ['ی', 'ي', 'ى'], 'ى' => ['ی', 'ي', 'ى'],
        ];
        $variants = [''];

        foreach (preg_split('//u', $term, -1, PREG_SPLIT_NO_EMPTY) as $character) {
            $next = [];
            foreach ($variants as $variant) {
                foreach ($choices[$character] ?? [$character] as $choice) {
                    $next[] = $variant.$choice;
                }
            }
            $variants = array_slice(array_values(array_unique($next)), 0, 256);
        }

        return $variants;
    }
}
