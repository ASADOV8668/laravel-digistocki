<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->with(['listing.brand', 'listing.phoneModel', 'listing.primaryImage'])
            ->whereHas('listing', fn ($query) => $query->published())
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('favorites.index', compact('favorites'));
    }

    public function toggle(Request $request, Listing $listing)
    {
        abort_unless($listing->status->value === 'approved' && ! $listing->isExpired(), 404);

        $favorite = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $message = 'آگهی از علاقه‌مندی‌ها حذف شد.';
            $favorited = false;
        } else {
            Favorite::create(['user_id' => $request->user()->id, 'listing_id' => $listing->id]);
            $message = 'آگهی به علاقه‌مندی‌ها اضافه شد.';
            $favorited = true;
        }

        if ($request->expectsJson()) {
            return response()->json(['favorited' => $favorited, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
