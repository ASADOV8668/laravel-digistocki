<?php

namespace App\Support;

final class SocialHandle
{
    /**
     * Keep only the account handle in the database. Full legacy URLs are
     * accepted at the boundary and converted to their first path segment.
     */
    public static function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = ltrim($value, '@');

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $path = trim((string) parse_url($value, PHP_URL_PATH), '/');
            $value = explode('/', $path)[0] ?? '';
            $value = ltrim($value, '@');
        }

        $value = preg_replace('/[?#].*$/', '', $value) ?? $value;
        $value = trim($value, ' /');

        return $value === '' ? null : $value;
    }

    public static function url(?string $value, string $platform): ?string
    {
        $handle = self::normalize($value);

        if ($handle === null) {
            return null;
        }

        $bases = [
            'instagram' => 'https://instagram.com/',
            'telegram' => 'https://t.me/',
            'whatsapp' => 'https://wa.me/',
            'bale' => 'https://ble.ir/',
            'rubika' => 'https://rubika.ir/',
        ];

        return isset($bases[$platform]) ? $bases[$platform].rawurlencode($handle) : null;
    }
}
