<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(15);
        $listingIds = collect($notifications->items())
            ->map(fn ($notification) => $notification->data['listing_id'] ?? null)
            ->filter(fn ($listingId) => is_numeric($listingId))
            ->map(fn ($listingId) => (int) $listingId)
            ->unique()
            ->values();
        $existingListingIds = $listingIds->isEmpty()
            ? []
            : Listing::query()->whereKey($listingIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $notifications->getCollection()->transform(function ($notification) use ($existingListingIds) {
            $data = $notification->data;
            if (array_key_exists('listing_id', $data)) {
                $data['listing_available'] = in_array((int) $data['listing_id'], $existingListingIds, true);
                $notification->setAttribute('data', $data);
            }

            return $notification;
        });

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notification)
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back()->with('status', 'اعلان خوانده‌شده علامت خورد.');
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'همه اعلان‌ها خوانده‌شده علامت خوردند.');
    }
}
