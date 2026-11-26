<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\Report;
use App\Models\SellerStore;
use App\Models\User;
use App\Support\PersianDate;

class BaleAdminReport
{
    public function text(): string
    {
        $time = now('Asia/Tehran');
        $start = $time->copy()->startOfDay()->setTimezone(config('app.timezone'));
        $end = $time->copy()->addDay()->startOfDay()->setTimezone(config('app.timezone'));

        return implode("\n", [
            'گزارش دیجی استوکی | '.PersianDate::format($time, 'Y/m/d H:i'),
            'کاربران جدید امروز: '.User::where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            'آگهی‌های جدید امروز: '.Listing::where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            'آگهی‌های در انتظار تأیید: '.Listing::where('status', 'pending')->count(),
            'فروشگاه‌های در انتظار تأیید: '.SellerStore::where('approval_status', 'pending')->count(),
            'گزارش‌های باز کاربران: '.Report::where('status', 'pending')->count(),
        ]);
    }
}
