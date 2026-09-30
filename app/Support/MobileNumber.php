<?php

namespace App\Support;

class MobileNumber
{
    public static function normalizeLocal(?string $mobile): ?string
    {
        if ($mobile === null || trim($mobile) === '') {
            return null;
        }

        return strtr(trim($mobile), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
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
