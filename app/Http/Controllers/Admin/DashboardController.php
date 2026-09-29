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
        $today = now()->startOfDay();
        $listingTrend = collect(range(6, 0))->map(function (int $days) use ($today) {
            $date = $today->copy()->subDays($days);

            return [
                'label' => $date->format('m/d'),
                'date' => $date->toDateString(),
                'count' => Listing::query()->whereBetween('created_at', [$date, $date->copy()->endOfDay()])->count(),
            ];
        });

        $statusCounts = Listing::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return view('admin.dashboard', [
            'usersCount' => User::query()->count(),
            'listingsCount' => Listing::query()->count(),
            'pendingListingsCount' => Listing::query()->where('status', 'pending')->count(),
            'approvedListingsCount' => Listing::query()->where('status', 'approved')->count(),
            'rejectedListingsCount' => Listing::query()->where('status', 'rejected')->count(),
            'expiredListingsCount' => Listing::query()->where('status', 'expired')->count(),
            'pendingReportsCount' => Report::query()->where('status', 'pending')->count(),
            'resolvedReportsCount' => Report::query()->where('status', 'resolved')->count(),
            'listingTrend' => $listingTrend,
            'statusCounts' => $statusCounts,
            'recentListings' => Listing::with(['user', 'brand', 'phoneModel'])->latest()->limit(6)->get(),
        ]);
    }
}
