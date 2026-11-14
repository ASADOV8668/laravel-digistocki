<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\SellerStore;
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
        $store = $this->storeForListing($user);

        return $user->is_active
            && $user->can_post_listings
            && $store instanceof SellerStore
            && ! $store->is_admin_disabled
            && (! $store->business_license_expires_at || ! $store->business_license_expires_at->isBefore(today()))
            && app(SystemOptions::class)->listingsEnabled()
            && $this->activeCount($user) < self::MAX_ACTIVE_LISTINGS;
    }

    public function canRenew(User $user): bool
    {
        $store = $this->storeForListing($user);

        return $user->is_active
            && $user->can_post_listings
            && $store instanceof SellerStore
            && ! $store->is_admin_disabled
            && (! $store->business_license_expires_at || ! $store->business_license_expires_at->isBefore(today()))
            && app(SystemOptions::class)->listingsEnabled()
            && $this->activeCount($user) < self::MAX_ACTIVE_LISTINGS;
    }

    private function storeForListing(User $user): ?SellerStore
    {
        return $user->relationLoaded('storefront')
            ? $user->storefront
            : $user->storefront()->first();
    }

    public function hasRecentDuplicate(User $user, int $brandId, int $phoneModelId, ?CarbonInterface $since = null, ?int $exceptListingId = null): bool
    {
        $cooldownMinutes = app(SystemOptions::class)->listingDuplicateCooldownMinutes();

        if ($since === null && $cooldownMinutes === 0) {
            return false;
        }

        return Listing::query()
            ->where('user_id', $user->id)
            ->where('brand_id', $brandId)
            ->where('phone_model_id', $phoneModelId)
            ->when($exceptListingId !== null, fn ($query) => $query->where('id', '!=', $exceptListingId))
            ->where('created_at', '>=', $since ?? now()->subMinutes($cooldownMinutes))
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

    public function canPublish(Listing $listing): bool
    {
        return in_array($listing->status, [ListingStatus::Pending, ListingStatus::Rejected], true);
    }

    public function reject(Listing $listing, string $reason): Listing
    {
        $listing->forceFill(['status' => ListingStatus::Rejected, 'rejection_reason' => $reason])->save();

        return $listing->refresh();
    }

    public function canReject(Listing $listing): bool
    {
        return in_array($listing->status, [ListingStatus::Pending, ListingStatus::Approved], true);
    }
}
