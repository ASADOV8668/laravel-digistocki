<?php

namespace App\Helpers;

use App\Services\SystemOptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        $authorization = config('services.sms.authorization');
        $patternCode = config('services.sms.pattern_code');
        if (! is_string($url) || trim($url) === '' || ! is_string($authorization) || trim($authorization) === '' || ! is_string($patternCode) || trim($patternCode) === '') {
            Log::warning('SMS live mode selected, but provider is not configured yet', [
                'mobile' => $mobile,
                'context' => $context,
            ]);

            return false;
        }

        $otp = self::otpFromContext($context, $message);
        if ($otp === null) {
            Log::error('SMS live mode could not determine the OTP code', [
                'mobile' => $mobile,
                'context' => $context,
            ]);

            return false;
        }

        try {
            $response = Http::timeout(max(1, (int) config('services.sms.timeout', 10)))
                ->asJson()
                ->acceptJson()
                ->withHeaders([
                    'Authorization' => $authorization,
                ])
                ->post($url, [
                    'sending_type' => config('services.sms.sending_type', 'pattern'),
                    'from_number' => config('services.sms.sender'),
                    'code' => $patternCode,
                    'recipients' => [$mobile],
                    'params' => ['otp' => $otp],
                ]);
        } catch (Throwable $exception) {
            Log::error('SMS provider request failed', [
                'mobile' => $mobile,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'context' => $context,
            ]);

            return false;
        }

        $providerAccepted = $response->successful() && data_get($response->json(), 'meta.status') === true;
        if ($providerAccepted) {
            Log::info('SMS sent through IPPanel pattern provider', ['mobile' => $mobile, 'context' => $context]);

            return true;
        }

        Log::error('IPPanel SMS provider rejected the request', [
            'mobile' => $mobile,
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
            'context' => $context,
        ]);

        return false;
    }

    private static function otpFromContext(array $context, string $message): ?string
    {
        $otp = $context['otp'] ?? null;
        if (is_string($otp) && preg_match('/^\d{4,6}$/', $otp) === 1) {
            return $otp;
        }

        if (preg_match('/(?<!\d)(\d{4,6})(?!\d)/', $message, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
