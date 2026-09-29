<?php

namespace App\Helpers;

use App\Services\SystemOptions;
use Illuminate\Support\Facades\Log;

class SmsHelper
{
    public static function send(string $mobile, string $message, array $context = []): bool
    {
        if (app(SystemOptions::class)->smsMode() === 'live') {
            return self::sendReal($mobile, $message, $context);
        }

        Log::info('SMS test mode: message was not sent', [
            'mobile' => $mobile,
            'message' => $message,
            ...$context,
        ]);

        return true;
    }

    public static function sendReal(string $mobile, string $message, array $context = []): bool
    {
        // این بدنه را بعداً با تابع وب‌سرویس سامانه پیامک جایگزین کنید.
        Log::warning('SMS live mode selected, but provider is not configured yet', [
            'mobile' => $mobile,
            'message' => $message,
            ...$context,
        ]);

        return false;
    }
}
