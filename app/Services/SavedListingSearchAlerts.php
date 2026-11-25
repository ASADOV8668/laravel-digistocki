<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\SavedListingSearch;
use App\Notifications\SavedListingSearchNotification;

class SavedListingSearchAlerts
{
    public function send(int $listingId, bool $priceDrop): void
    {
        $listing = Listing::query()->published()->find($listingId);
        if ($listing === null) {
            return;
        }

        $search = app(NaturalListingSearch::class);
        $recipients = [];
        $matches = [];
        SavedListingSearch::query()->where('phone_model_id', $listing->phone_model_id)
            ->where('user_id', '!=', $listing->user_id)->with('user')->chunkById(100, function ($subscriptions) use ($listing, $search, $priceDrop, &$recipients, &$matches): void {
                foreach ($subscriptions as $subscription) {
                    if (! $subscription->user?->is_active || isset($recipients[$subscription->user_id])) {
                        continue;
                    }
                    $matches[$subscription->fingerprint] ??= $search->apply(Listing::query()->whereKey($listing->id), $subscription->criteria)->exists();
                    if ($matches[$subscription->fingerprint]) {
                        $subscription->user->notify(new SavedListingSearchNotification($listing, $priceDrop));
                        $recipients[$subscription->user_id] = true;
                    }
                }
            });
    }
}
