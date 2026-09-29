<?php

namespace App\Services;

use App\Helpers\SmsHelper;
use App\Models\ContactOtp;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ContactOtpService
{
    public const TTL_MINUTES = 5;
    public const MAX_ATTEMPTS = 5;

    public function issue(User $user, Listing $listing): ContactOtp
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);
        ContactOtp::query()->where('user_id', $user->id)->where('listing_id', $listing->id)->whereNull('verified_at')->update(['verified_at' => now()]);

        $otp = ContactOtp::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
        ]);

        SmsHelper::send($user->mobile, "کد تأیید شما: {$code}", [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'expires_at' => $otp->expires_at->toIso8601String(),
        ]);

        return $otp;
    }

    public function verify(User $user, Listing $listing, string $code): bool
    {
        $otp = ContactOtp::query()->where('user_id', $user->id)->where('listing_id', $listing->id)->whereNull('verified_at')->latest()->first();
        $expiresAt = $otp ? Carbon::parse($otp->getRawOriginal('expires_at'), config('app.timezone')) : null;
        if (! $otp || $expiresAt->isPast() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $otp->forceFill(['attempts' => $otp->attempts + 1])->save();
        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->forceFill(['verified_at' => now()])->save();
        return true;
    }
}
