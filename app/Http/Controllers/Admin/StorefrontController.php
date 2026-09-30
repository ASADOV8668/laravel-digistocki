<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStorefrontRequest;
use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Sadegh19b\LaravelIranCities\Models\Province;

class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $status = is_string($status) && in_array($status, ['active', 'disabled', 'blocked'], true) ? $status : null;
        $storeStats = [
            'total' => SellerStore::query()->count(),
            'active' => SellerStore::query()->where('is_enabled', true)->where('is_admin_disabled', false)->count(),
            'disabled' => SellerStore::query()->where('is_enabled', false)->where('is_admin_disabled', false)->count(),
            'blocked' => SellerStore::query()->where('is_admin_disabled', true)->count(),
        ];

        $stores = SellerStore::query()
            ->with(['user', 'province', 'city'])
            ->when($status === 'active', fn (Builder $query) => $query->where('is_enabled', true)->where('is_admin_disabled', false))
            ->when($status === 'disabled', fn (Builder $query) => $query->where('is_enabled', false)->where('is_admin_disabled', false))
            ->when($status === 'blocked', fn (Builder $query) => $query->where('is_admin_disabled', true))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(function (Builder $search) use ($term): void {
                    $search->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('mobile', 'like', $term));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.storefronts.index', compact('stores', 'storeStats'));
    }

    public function edit(User $user): View
    {
        return view('admin.storefronts.edit', [
            'user' => $user,
            'store' => $user->storefront,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function toggle(Request $request, SellerStore $sellerStore): RedirectResponse
    {
        $sellerStore->update(['is_admin_disabled' => ! $sellerStore->is_admin_disabled]);

        return back()->with('status', $sellerStore->is_admin_disabled ? 'غرفه توسط مدیریت غیرفعال شد.' : 'غرفه از حالت غیرفعال مدیریتی خارج شد.');
    }

    public function update(UpdateStorefrontRequest $request, User $user): RedirectResponse
    {
        $store = $user->storefront;
        $validated = $request->validated();
        $logoPath = $store?->logo_path;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('stores', 'public');
            if ($store?->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
        }

        $payload = [
            'user_id' => $user->id,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'is_enabled' => (bool) ($validated['store_enabled'] ?? false),
            'is_admin_disabled' => (bool) ($validated['admin_disabled'] ?? false),
            'province_id' => $validated['province_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'logo_path' => $logoPath,
            'address' => $validated['address'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? $user->mobile,
        ];

        if ($store) {
            $store->update($payload);
        } else {
            SellerStore::create($payload);
        }

        return redirect()->route('admin.storefronts.index')->with('status', 'اطلاعات غرفه به‌روزرسانی شد.');
    }
}
