<?php

namespace App\Console\Commands;

use App\Models\ContactOtp;
use Illuminate\Console\Command;

class PruneContactOtps extends Command
{
    protected $signature = 'contact-otps:prune {--hours=24 : Keep OTP history for this many hours}';

    protected $description = 'Remove expired and consumed contact OTP records';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $deleted = ContactOtp::query()
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($query) use ($cutoff) {
                    $query->whereNotNull('verified_at')->where('verified_at', '<', $cutoff);
                })->orWhere('expires_at', '<', $cutoff);
            })
            ->delete();

        $this->info("Pruned {$deleted} contact OTP records.");

        return self::SUCCESS;
    }
}
