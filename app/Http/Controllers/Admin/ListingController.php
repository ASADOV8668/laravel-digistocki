<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Notifications\ListingStatusNotification;
use App\Services\ListingRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $statuses = ['pending', 'approved', 'rejected', 'sold', 'expired'];
        $statusCounts = Listing::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $listings = Listing::with(['user', 'brand', 'phoneModel', 'primaryImage'])
            ->when(in_array($status, $statuses, true), fn (Builder $query) => $query->where('status', $status))
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function (Builder $query) use ($term) {
                    $query->where('title', 'like', $term)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('mobile', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $term)->orWhere('name_en', 'like', $term))
                        ->orWhereHas('phoneModel', fn (Builder $model) => $model->where('name', 'like', $term)->orWhere('name_fa', 'like', $term)->orWhere('name_en', 'like', $term));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.listings.index', compact('listings', 'statuses', 'status', 'statusCounts'));
    }

    public function approve(Listing $listing, ListingRules $rules)
    {
        $this->authorize('approve', $listing);
        $listing = $rules->publish($listing);
        $listing->user->notify(new ListingStatusNotification($listing, 'approved'));

        return back()->with('status', 'آگهی تأیید شد.');
    }

    public function reject(Request $request, Listing $listing, ListingRules $rules)
    {
        $this->authorize('reject', $listing);
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $listing = $rules->reject($listing, $validated['rejection_reason']);
        $listing->user->notify(new ListingStatusNotification($listing, 'rejected', $validated['rejection_reason']));

        return back()->with('status', 'آگهی رد شد.');
    }
}
