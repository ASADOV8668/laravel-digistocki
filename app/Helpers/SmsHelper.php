<?php

namespace App\Helpers;

use App\Services\SystemOptions;
use Illuminate\Support\Facades\Http;
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
        $url = config('services.sms.url');
        if (! is_string($url) || trim($url) === '') {
            Log::warning('SMS live mode selected, but provider is not configured yet', [
                'mobile' => $mobile,
                'context' => $context,
            ]);

            return false;
        }

        $request = Http::timeout(max(1, (int) config('services.sms.timeout', 10)));
        $token = config('services.sms.token');
        if (is_string($token) && $token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'mobile' => $mobile,
            'message' => $message,
            'sender' => config('services.sms.sender'),
        ]);
        if ($response->successful()) {
            Log::info('SMS sent through configured provider', ['mobile' => $mobile, 'context' => $context]);

            return true;
        }

        Log::error('Configured SMS provider rejected the request', [
            'mobile' => $mobile,
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
            'context' => $context,
        ]);

        return false;
    }
}
