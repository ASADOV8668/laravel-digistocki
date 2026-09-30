<?php

namespace App\Services;

use App\Helpers\SmsHelper;
use App\Models\MobileOtp;
use App\Support\OtpCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MobileOtpService
{
    public const MAX_ATTEMPTS = 5;

    public function issue(string $mobile, string $purpose): ?MobileOtp
    {
        $code = OtpCode::generate();
        $expiresAt = now()->addMinutes(app(SystemOptions::class)->otpExpiryMinutes());

        if (! SmsHelper::send($mobile, "کد تأیید شما: {$code}", compact('mobile', 'purpose', 'expiresAt'))) {
            return null;
        }

        return DB::transaction(function () use ($mobile, $purpose, $code, $expiresAt): MobileOtp {
            MobileOtp::query()
                ->where('mobile', $mobile)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->update(['verified_at' => now()]);

            return MobileOtp::create([
                'mobile' => $mobile,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function verify(string $mobile, string $purpose, string $code): bool
    {
        return DB::transaction(function () use ($mobile, $purpose, $code): bool {
            $otp = MobileOtp::query()
                ->where('mobile', $mobile)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
                return false;
            }

            $otp->forceFill(['attempts' => $otp->attempts + 1])->save();
            if (! Hash::check($code, $otp->code_hash)) {
                return false;
            }

            $otp->forceFill(['verified_at' => now()])->save();

            return true;
        });
    }
}
