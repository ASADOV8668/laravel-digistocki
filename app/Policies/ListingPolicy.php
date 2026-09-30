<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;
use App\Services\ListingRules;

class ListingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Listing $listing): bool
    {
        return ($listing->status->value === 'approved' && ! $listing->isExpired())
            || (bool) $user?->isAdmin()
            || (bool) $user?->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return app(ListingRules::class)->canCreate($user);
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->isAdmin() || ($listing->isOwnedBy($user) && in_array($listing->status->value, ['pending', 'rejected'], true));
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->isAdmin() || $listing->isOwnedBy($user);
    }

    public function approve(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user): bool
    {
        return $user->isAdmin();
    }

    public function markSold(User $user, Listing $listing): bool
    {
        return ($user->isAdmin() || $listing->isOwnedBy($user)) && $listing->status->value === 'approved';
    }

    public function renew(User $user, Listing $listing): bool
    {
        return $listing->isOwnedBy($user) && $listing->status->value === 'expired';
    }
}
