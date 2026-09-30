<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Carbon\CarbonInterface;

class ListingRules
{
    public const MAX_ACTIVE_LISTINGS = 10;

    public const EXPIRY_DAYS = 30;

    public function activeCount(User $user): int
    {
        return Listing::query()->active()->where('user_id', $user->id)->count();
    }

    public function canCreate(User $user): bool
    {
        return $user->is_active
            && $user->can_post_listings
            && app(SystemOptions::class)->listingsEnabled()
            && $this->activeCount($user) < self::MAX_ACTIVE_LISTINGS;
    }

    public function canRenew(User $user): bool
    {
        return $user->is_active
            && $user->can_post_listings
            && app(SystemOptions::class)->listingsEnabled()
            && $this->activeCount($user) < self::MAX_ACTIVE_LISTINGS;
    }

    public function hasRecentDuplicate(User $user, int $brandId, int $phoneModelId, ?CarbonInterface $since = null, ?int $exceptListingId = null): bool
    {
        return Listing::query()
            ->where('user_id', $user->id)
            ->where('brand_id', $brandId)
            ->where('phone_model_id', $phoneModelId)
            ->when($exceptListingId !== null, fn ($query) => $query->where('id', '!=', $exceptListingId))
            ->where('created_at', '>=', $since ?? now()->subDay())
            ->exists();
    }

    public function expiryDate(?CarbonInterface $from = null): CarbonInterface
    {
        return ($from ?? now())->copy()->addDays(self::EXPIRY_DAYS);
    }

    public function publish(Listing $listing): Listing
    {
        $listing->forceFill(['status' => ListingStatus::Approved, 'published_at' => now(), 'expires_at' => $this->expiryDate()])->save();

        return $listing->refresh();
    }

    public function reject(Listing $listing, string $reason): Listing
    {
        $listing->forceFill(['status' => ListingStatus::Rejected, 'rejection_reason' => $reason])->save();

        return $listing->refresh();
    }
}
