<?php

namespace App\Enums;

enum ListingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Sold = 'sold';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار بررسی',
            self::Approved => 'تأیید شده',
            self::Rejected => 'رد شده',
            self::Sold => 'فروخته شده',
            self::Expired => 'منقضی شده',
        };
    }
}
