<?php

namespace App\Support;

final class StorageCapacity
{
    public static function gigabytes(mixed $value): ?int
    {
        if ((! is_int($value) && ! is_string($value))
            || ! preg_match('/\A(\d{1,4})\h*(gb?|tb?|گیگ(?:ابایت)?|ترابایت)?\z/iu', trim(PersianDigits::toEnglish((string) $value)), $parts)) {
            return null;
        }

        $unit = mb_strtolower($parts[2] ?? '');
        $capacity = (int) $parts[1] * (in_array($unit, ['t', 'tb', 'ترابایت'], true) ? 1024 : 1);

        return $capacity >= 1 && $capacity <= 9999 ? $capacity : null;
    }

    public static function forAttribute(mixed $value, array $definition): int|string|null
    {
        $isSelect = ($definition['type'] ?? null) === 'select';
        $options = $definition['options'] ?? [];

        if ($isSelect && (is_int($value) || is_string($value)) && in_array((string) $value, $options, true)) {
            return (string) $value;
        }

        $gigabytes = self::gigabytes($value);
        if ($gigabytes === null || ! $isSelect) {
            return $gigabytes;
        }

        foreach ($options as $option) {
            if (self::gigabytes($option) === $gigabytes) {
                return $option;
            }
        }

        return (string) $gigabytes;
    }
}
