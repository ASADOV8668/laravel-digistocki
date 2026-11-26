<?php

namespace App\Support;

final class RegistrationStatus
{
    public const REGISTERED = 'رجیستر شده';

    public const OWNER = 'رجیستر شده به نام خودم';

    public const OTHER = 'رجیستر شده به نام دیگران';

    public const UNREGISTERED = 'رجیستر نشده';

    public const UNKNOWN = 'نامشخص';

    public static function detect(string $text): string
    {
        $text = str_replace(['ي', 'ك', "\u{200C}"], ['ی', 'ک', ' '], $text);
        $negative = preg_match('/❌|🚫|✖|\b(?:unregistered|not\h+registered|registry\h*[:=]?\h*no)\b|رجیستر(?:ی)?\h*(?:نشده|نیست|ندارد)|بدون\h+رجیستر(?:ی)?/iu', $text);
        $positive = preg_match('/✅|☑|✔|\bregistered\b|\bregistry\h*[:=]?\h*yes\b|رجیستر(?:ی)?\h*(?:شده|دارد)|با\h+رجیستری/iu',
            preg_replace('/\bnot\h+registered\b/iu', '', $text));

        if ($positive === $negative) {
            return self::UNKNOWN;
        }
        if ($negative) {
            return self::UNREGISTERED;
        }
        if (preg_match('/(?:به\h+)?نام\h+(?:خودم|خود\h+فروشنده)/u', $text)) {
            return self::OWNER;
        }
        if (preg_match('/(?:به\h+)?نام\h+(?:دیگران|شخص\h+دیگر|فرد\h+دیگر)/u', $text)) {
            return self::OTHER;
        }

        return self::REGISTERED;
    }

    public static function registered(?string $status): ?bool
    {
        return match ($status) {
            self::REGISTERED, self::OWNER, self::OTHER => true,
            self::UNREGISTERED => false,
            default => null,
        };
    }

    public static function fromBoolean(?bool $registered): string
    {
        return match ($registered) {
            true => self::REGISTERED,
            false => self::UNREGISTERED,
            null => self::UNKNOWN,
        };
    }
}
