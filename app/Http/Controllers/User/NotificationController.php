<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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

    public function markAsRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'اعلان خوانده‌شده علامت خورد.',
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back()->with('status', 'اعلان خوانده‌شده علامت خورد.');
    }

    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'همه اعلان‌ها خوانده‌شده علامت خوردند.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('status', 'همه اعلان‌ها خوانده‌شده علامت خوردند.');
    }
}
