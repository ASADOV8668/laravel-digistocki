<?php

namespace App\Support;

use App\Services\SystemOptions;

final class OtpCode
{
    public const TEST_CODE = '00000';

    public static function generate(): string
    {
        return app(SystemOptions::class)->smsMode() === 'test'
            ? self::TEST_CODE
            : (string) random_int(10000, 99999);
    }
}
