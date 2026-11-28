<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListingPackageOrderController extends Controller
{
    public function index(): View
    {
        return view('admin.finance.orders', [
            'packages' => DB::table('listing_packages')->orderBy('name')->get(['id', 'name']),
            'stores' => DB::table('seller_stores')->orderBy('name')->get(['id', 'name']),
            'orders' => DB::table('listing_package_orders')
                ->orderByDesc('listing_package_orders.id')->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seller_store_id' => ['required', 'integer', 'exists:seller_stores,id'],
            'listing_package_id' => ['required', 'integer', 'exists:listing_packages,id'],
            'amount_toman' => ['required', 'integer', 'between:0,1000000000000'],
        ]);

        $package = DB::table('listing_packages')->find($data['listing_package_id']);
        $store = DB::table('seller_stores')->find($data['seller_store_id']);
        abort_if($package === null || $store === null, 404);
        $now = now();

        DB::table('listing_package_orders')->insert($data + [
            'reference' => (string) Str::uuid(),
            'created_by' => $request->user()->id,
            'package_name' => $package->name,
            'store_name' => $store->name,
            'publication_quota' => $package->publication_quota,
            'active_listing_limit' => $package->active_listing_limit,
            'duration_days' => $package->duration_days,
            'status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return to_route('admin.finance.orders.index')->with('status', 'پیش‌نویس سفارش ثبت شد.');
    }
}
