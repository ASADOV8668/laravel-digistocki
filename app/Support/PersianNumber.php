<?php

namespace App\Support;

final class PersianNumber
{
    public static function format(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = is_numeric($value) ? number_format((float) $value, 0, '.', ',') : (string) $value;

        return self::digits($formatted);
    }

    public static function digits(int|float|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}
