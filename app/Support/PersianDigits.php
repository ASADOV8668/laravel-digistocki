<?php

namespace App\Support;

final class PersianDigits
{
    public static function toEnglish(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    public static function normalizeRecursive(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalizedKey = is_string($key) ? self::toEnglish($key) : $key;
                $normalized[$normalizedKey] = self::normalizeRecursive($item);
            }

            return $normalized;
        }

        return self::toEnglish($value);
    }
}
