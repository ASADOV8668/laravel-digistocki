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

    public static function fromParts(mixed $year, mixed $month, mixed $day): ?string
    {
        $parts = array_map(static fn (mixed $value): string => self::toEnglishDigits(trim((string) $value)), [$year, $month, $day]);

        if (in_array('', $parts, true) || ! array_reduce($parts, static fn (bool $carry, string $part): bool => $carry && ctype_digit($part), true)) {
            return null;
        }

        return sprintf('%04d/%02d/%02d', (int) $parts[0], (int) $parts[1], (int) $parts[2]);
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
