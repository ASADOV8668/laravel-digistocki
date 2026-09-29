<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\ListingLifecycleService;
use Illuminate\Console\Command;

class ExpireListings extends Command
{
    protected $signature = 'listings:expire {--dry-run : فقط تعداد آگهی‌های قابل انقضا را نمایش بده}';

    protected $description = 'آگهی‌های تأییدشده‌ای را که تاریخ انقضایشان گذشته است، منقضی می‌کند';

    public function handle(ListingLifecycleService $lifecycle): int
    {
        $due = Listing::query()
            ->where('status', ListingStatus::Approved)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->count();

        if ($this->option('dry-run')) {
            $this->info("{$due} آگهی آماده‌ی انقضا است.");

            return self::SUCCESS;
        }

        $expired = $lifecycle->expireDueListings();
        $this->info("{$expired} آگهی منقضی شد.");

        return self::SUCCESS;
    }
}
