<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStorefrontRequest;
use App\Models\Listing;
use App\Models\SellerStore;
use App\Support\PersianDate;
use App\Support\PersianSearch;
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
        $cityIds = collect((array) $request->input('city_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $stores = SellerStore::query()
            ->with(['province', 'city'])
            ->where('approval_status', 'approved')
            ->where('is_enabled', true)
            ->where('is_admin_disabled', false)
            ->when($request->filled('province_id'), fn (Builder $query) => $query->where('province_id', (int) $request->input('province_id')))
            ->when($cityIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('city_id', $cityIds->all()))
            ->orderByRaw('CASE WHEN sort_order > 0 THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN sort_order > 0 THEN sort_order ELSE 0 END')
            ->orderByRaw('RAND()')
            ->paginate(18)
            ->withQueryString();

        return view('storefront.index', [
            'stores' => $stores,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'selectedProvince' => $request->input('province_id'),
            'selectedCityIds' => $cityIds->all(),
        ]);
    }

    public function show(Request $request, SellerStore $sellerStore): View
    {
        $sellerStore->load(['user', 'province', 'city']);

        if (! $sellerStore->isPubliclyEnabled()) {
            return view('storefront.disabled', ['store' => $sellerStore]);
        }

        $search = trim((string) $request->input('q'));
        $searchLikes = PersianSearch::likes($search);
        $listings = Listing::query()
            ->published()
            ->where('user_id', $sellerStore->user_id)
            ->when($searchLikes !== [], function (Builder $query) use ($searchLikes): void {
                $query->where(function (Builder $listingQuery) use ($searchLikes): void {
                    foreach ($searchLikes as $like) {
                        $listingQuery->orWhere('title', 'like', $like)
                            ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $like)->orWhere('name_en', 'like', $like))
                            ->orWhereHas('phoneModel', fn (Builder $model) => $model->where('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like));
                    }
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
        $businessLicensePath = $store?->business_license_path;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('stores', 'public');
            if ($store?->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
        }

        if ($request->hasFile('business_license')) {
            $businessLicensePath = $request->file('business_license')->store('business-licenses', 'public');
            if ($store?->business_license_path) {
                Storage::disk('public')->delete($store->business_license_path);
            }
        }

        $approvalStatus = $store?->approval_status;
        if (! $store || $store->isRejected()) {
            $approvalStatus = 'pending';
        }

        $payload = [
            'name' => $validated['name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'national_id' => $validated['national_id'],
            'approval_status' => $approvalStatus ?? 'pending',
            'is_enabled' => $approvalStatus === 'approved' && ! $store?->is_admin_disabled && $request->boolean('store_enabled'),
            'province_id' => $validated['province_id'],
            'city_id' => $validated['city_id'],
            'logo_path' => $logoPath,
            'address' => $validated['address'],
            'contact_phone' => $validated['contact_phone'],
            'business_license_number' => $validated['business_license_number'],
            'business_license_expires_at' => PersianDate::parseDate($validated['business_license_expires_at'])?->toDateString(),
            'business_license_path' => $businessLicensePath,
            'instagram_url' => $validated['instagram_url'] ?? null,
            'telegram_url' => $validated['telegram_url'] ?? null,
            'whatsapp_url' => $validated['whatsapp_url'] ?? null,
            'bale_url' => $validated['bale_url'] ?? null,
            'rubika_url' => $validated['rubika_url'] ?? null,
            'application_submitted_at' => $store?->application_submitted_at ?? now(),
            'rejection_reason' => $approvalStatus === 'pending' ? null : $store?->rejection_reason,
        ];

        if (! $store) {
            $payload['slug'] = $validated['slug'];
            $store = $user->storefront()->create($payload);
        } else {
            if ($store->isRejected()) {
                $payload['application_submitted_at'] = now();
            }
            $store->update($payload);
        }

        $user->forceFill([
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'national_id' => $validated['national_id'],
        ])->save();

        return back()->with('status', $store->isApproved() ? 'اطلاعات فروشگاه شما ذخیره شد.' : 'درخواست فروشگاه شما ثبت شد و پس از بررسی مدیریت اعلام نتیجه خواهد شد.');
    }
}
