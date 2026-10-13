<?php

namespace App\Support;

class MobileNumber
{
    public static function normalizeLocal(?string $mobile): ?string
    {
        if ($mobile === null || trim($mobile) === '') {
            return null;
        }

        return PersianDigits::toEnglish(trim($mobile));
    }

    public static function normalize(?string $mobile): ?string
    {
        $mobile = self::normalizeLocal($mobile);
        if ($mobile === null) {
            return null;
        }

        $mobile = preg_replace('/[\s\-()]+/', '', $mobile) ?? $mobile;

        if (str_starts_with($mobile, '+98')) {
            $mobile = '0'.substr($mobile, 3);
        } elseif (str_starts_with($mobile, '0098')) {
            $mobile = '0'.substr($mobile, 4);
        }

        return $mobile;
    }
}
