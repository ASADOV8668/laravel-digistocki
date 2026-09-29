<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'usersCount' => User::query()->count(),
            'listingsCount' => Listing::query()->count(),
            'pendingListingsCount' => Listing::query()->where('status', 'pending')->count(),
            'approvedListingsCount' => Listing::query()->where('status', 'approved')->count(),
            'rejectedListingsCount' => Listing::query()->where('status', 'rejected')->count(),
            'pendingReportsCount' => Report::query()->where('status', 'pending')->count(),
            'recentListings' => Listing::with(['user', 'brand', 'phoneModel'])->latest()->limit(6)->get(),
        ]);
    }
}
