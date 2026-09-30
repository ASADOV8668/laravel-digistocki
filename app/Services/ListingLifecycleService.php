<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Notifications\ListingStatusNotification;
use Carbon\CarbonInterface;

class ListingLifecycleService
{
    public function expireDueListings(?CarbonInterface $now = null): int
    {
        $expired = 0;

        Listing::query()
            ->where('status', ListingStatus::Approved)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now ?? now())
            ->with('user')
            ->chunkById(100, function ($listings) use (&$expired) {
                foreach ($listings as $listing) {
                    $changed = Listing::query()
                        ->whereKey($listing->getKey())
                        ->where('status', ListingStatus::Approved)
                        ->update([
                            'status' => ListingStatus::Expired,
                            'updated_at' => now(),
                        ]);

                    if ($changed !== 1) {
                        continue;
                    }

                    $listing->forceFill(['status' => ListingStatus::Expired]);
                    $listing->user->notify(new ListingStatusNotification($listing, 'expired'));
                    $expired++;
                }
            });

        return $expired;
    }
}
