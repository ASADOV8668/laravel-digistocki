<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use App\Support\PersianDate;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->startOfDay();
        $trendStart = $today->copy()->subDays(6);
        $trendCounts = Listing::query()
            ->whereBetween('created_at', [$trendStart, $today->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $listingTrend = collect(range(6, 0))->map(function (int $days) use ($today, $trendCounts) {
            $date = $today->copy()->subDays($days);

            return [
                'label' => PersianDate::format($date, 'm/d'),
                'date' => $date->toDateString(),
                'count' => (int) $trendCounts->get($date->toDateString(), 0),
            ];
        });

        $statusCounts = Listing::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $reportStatusCounts = Report::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return view('admin.dashboard', [
            'usersCount' => User::query()->count(),
            'listingsCount' => (int) $statusCounts->sum(),
            'pendingListingsCount' => (int) $statusCounts->get('pending', 0),
            'approvedListingsCount' => (int) $statusCounts->get('approved', 0),
            'rejectedListingsCount' => (int) $statusCounts->get('rejected', 0),
            'expiredListingsCount' => (int) $statusCounts->get('expired', 0),
            'pendingReportsCount' => (int) $reportStatusCounts->get('pending', 0),
            'resolvedReportsCount' => (int) $reportStatusCounts->get('resolved', 0),
            'listingTrend' => $listingTrend,
            'statusCounts' => $statusCounts,
            'listingStatusChart' => ['labels' => ['در انتظار', 'تأییدشده', 'ردشده', 'فروخته‌شده', 'منقضی‌شده'], 'values' => [$statusCounts->get('pending', 0), $statusCounts->get('approved', 0), $statusCounts->get('rejected', 0), $statusCounts->get('sold', 0), $statusCounts->get('expired', 0)]],
            'recentListings' => Listing::with(['user', 'brand', 'phoneModel'])->latest()->limit(6)->get(),
        ]);
    }
}
