<?php

namespace App\Support;

final class SocialHandle
{
    public static function fromMobile(?string $mobile): ?string
    {
        $value = strtr((string) $mobile, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $value = preg_replace('/\D+/', '', $value) ?? '';

        if (preg_match('/^09\d{9}$/', $value) === 1) {
            return '98'.substr($value, 1);
        }

        return preg_match('/^989\d{9}$/', $value) === 1 ? $value : null;
    }

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
