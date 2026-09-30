<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Hekmatinasser\Verta\Verta;

final class PersianDate
{
    public static function format(DateTimeInterface|string|null $date, string $format = 'Y/m/d'): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return verta($date)->format($format);
    }

    public static function human(DateTimeInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return verta($date)->formatDifference();
    }

    public static function parseDate(?string $date): ?Carbon
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        try {
            return Carbon::instance(Verta::parse(self::toEnglishDigits($date))->datetime())->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function toEnglishDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
