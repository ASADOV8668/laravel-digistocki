<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
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

    public function destroyAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->notifications()->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'همه اعلان‌ها حذف شدند.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('status', 'همه اعلان‌ها حذف شدند.');
    }
}
