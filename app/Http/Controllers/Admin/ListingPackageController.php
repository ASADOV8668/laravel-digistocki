<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListingPackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeListingArea($request);

        return view('admin.listing-packages.index', [
            'packages' => DB::table('listing_packages')->orderBy('name')->get(),
            'stores' => DB::table('seller_stores')->orderBy('name')->get(['id', 'name']),
            'assignments' => DB::table('store_listing_packages')
                ->join('seller_stores', 'seller_stores.id', '=', 'store_listing_packages.seller_store_id')
                ->join('listing_packages', 'listing_packages.id', '=', 'store_listing_packages.listing_package_id')
                ->select('store_listing_packages.*', 'seller_stores.name as store_name', 'listing_packages.name as package_name')
                ->orderByDesc('store_listing_packages.id')
                ->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeListingArea($request);
        $package = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'publication_quota' => ['required', 'integer', 'between:1,1000000'],
            'active_listing_limit' => ['required', 'integer', 'between:1,1000000'],
            'duration_days' => ['required', 'integer', 'between:1,3650'],
        ]);

        DB::table('listing_packages')->insert($package + ['created_at' => now(), 'updated_at' => now()]);

        return to_route('admin.listing-packages.index')->with('status', 'بستهٔ آگهی ثبت شد.');
    }

    public function assign(Request $request): RedirectResponse
    {
        $this->authorizeListingArea($request);
        $data = $request->validate([
            'seller_store_id' => ['required', 'integer', 'exists:seller_stores,id'],
            'listing_package_id' => ['required', 'integer', 'exists:listing_packages,id'],
        ]);

        $package = DB::table('listing_packages')->find($data['listing_package_id']);
        abort_if($package === null, 404);
        $now = now();

        DB::table('store_listing_packages')->insert($data + [
            'publication_quota' => $package->publication_quota,
            'active_listing_limit' => $package->active_listing_limit,
            'starts_at' => $now,
            'ends_at' => $now->copy()->addDays($package->duration_days),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return to_route('admin.listing-packages.index')->with('status', 'بسته به فروشگاه تخصیص یافت.');
    }

    private function authorizeListingArea(Request $request): void
    {
        $user = $request->user();
        abort_if(method_exists($user, 'canAccessAdminArea') && ! $user->canAccessAdminArea('listings'), 403);
    }
}
