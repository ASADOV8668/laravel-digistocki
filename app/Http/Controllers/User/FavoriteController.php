<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $favorited = DB::transaction(function () use ($request, $listing): bool {
            $lockedListing = Listing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedListing->status->value === 'approved' && ! $lockedListing->isExpired(), 404);

            $favorite = Favorite::query()
                ->where('user_id', $request->user()->id)
                ->where('listing_id', $lockedListing->id)
                ->first();

            if ($favorite) {
                $favorite->delete();

                return false;
            }

            Favorite::create(['user_id' => $request->user()->id, 'listing_id' => $lockedListing->id]);

            return true;
        });
        $message = $favorited ? 'آگهی به علاقه‌مندی‌ها اضافه شد.' : 'آگهی از علاقه‌مندی‌ها حذف شد.';

        if ($request->expectsJson()) {
            return response()->json(['favorited' => $favorited, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
