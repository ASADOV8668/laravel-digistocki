<?php

namespace App\Console\Commands;

use App\Models\MobileOtp;
use Illuminate\Console\Command;

class PruneMobileOtps extends Command
{
    protected $signature = 'mobile-otps:prune {--hours=24 : Keep OTP history for this many hours}';

    protected $description = 'Remove expired and consumed mobile OTP records';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);
        $deleted = MobileOtp::query()
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($query) use ($cutoff) {
                    $query->whereNotNull('verified_at')->where('verified_at', '<=', $cutoff);
                })->orWhere('expires_at', '<=', $cutoff);
            })
            ->delete();

        $this->info("Pruned {$deleted} mobile OTP records.");

        return self::SUCCESS;
    }
}
