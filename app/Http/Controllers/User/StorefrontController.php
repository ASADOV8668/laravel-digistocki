<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStorefrontRequest;
use App\Models\Listing;
use App\Models\SellerStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Sadegh19b\LaravelIranCities\Models\Province;

class StorefrontController extends Controller
{
    public function show(Request $request, SellerStore $sellerStore): View
    {
        $sellerStore->load(['user', 'province', 'city']);

        if (! $sellerStore->isPubliclyEnabled()) {
            return view('storefront.disabled', ['store' => $sellerStore]);
        }

        $search = trim((string) $request->input('q'));
        $listings = Listing::query()
            ->published()
            ->where('user_id', $sellerStore->user_id)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';
                $query->where(function (Builder $listingQuery) use ($term): void {
                    $listingQuery->where('title', 'like', $term)
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $term)->orWhere('name_en', 'like', $term))
                        ->orWhereHas('phoneModel', fn (Builder $model) => $model->where('name', 'like', $term)->orWhere('name_fa', 'like', $term)->orWhere('name_en', 'like', $term));
                });
            })
            ->with(['brand', 'phoneModel', 'province', 'city', 'primaryImage', 'attributeValues.attribute', 'user.storefront'])
            ->withViewerFavorite($request->user()?->id)
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.show', [
            'store' => $sellerStore,
            'listings' => $listings,
        ]);
    }

    public function edit(Request $request): View
    {
        return view('storefront.edit', [
            'store' => $request->user()->storefront,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateStorefrontRequest $request): RedirectResponse
    {
        $user = $request->user();
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
            'name' => $validated['name'] ?? $store?->name ?? $user->name,
            'is_enabled' => $store?->is_admin_disabled
                ? (bool) $store->is_enabled
                : $request->boolean('store_enabled'),
            'province_id' => $validated['province_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'logo_path' => $logoPath,
            'address' => $validated['address'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? $user->mobile,
        ];

        if (! $store) {
            $payload['slug'] = $validated['slug'];
            $store = $user->storefront()->create($payload);
        } else {
            $store->update($payload);
        }

        return back()->with('status', 'اطلاعات غرفه شما ذخیره شد.');
    }
}
