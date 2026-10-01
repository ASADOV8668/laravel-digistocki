<?php

namespace App\Support;

final class ColorPalette
{
    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            'مشکی' => '#111827',
            'سفید' => '#ffffff',
            'طلایی' => '#d4a72c',
            'نقره‌ای' => '#cbd5e1',
            'آبی' => '#2563eb',
            'سبز' => '#16a34a',
            'بنفش' => '#9333ea',
            'خاکستری' => '#6b7280',
            'قرمز' => '#dc2626',
            'صورتی' => '#ec4899',
            'نارنجی' => '#ea580c',
            'سرمه‌ای' => '#172554',
            'مسی' => '#b45309',
            'تیفانی' => '#14b8a6',
            'کرم' => '#f5e6c8',
            'زرد' => '#eab308',
        ];
    }

    public static function hex(?string $name): string
    {
        return self::all()[$name ?? ''] ?? '#94a3b8';
    }
}
