<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStorefrontRequest;
use App\Models\SellerStore;
use App\Models\User;
use App\Notifications\StorefrontStatusNotification;
use App\Support\PersianDate;
use App\Support\PersianSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
        $searchLikes = PersianSearch::likes((string) $request->input('q'));
        $storeStats = [
            'total' => SellerStore::query()->count(),
            'active' => SellerStore::query()->where('is_enabled', true)->where('is_admin_disabled', false)->count(),
            'disabled' => SellerStore::query()->where('is_enabled', false)->where('is_admin_disabled', false)->count(),
            'blocked' => SellerStore::query()->where('is_admin_disabled', true)->count(),
            'pending' => SellerStore::query()->where('approval_status', 'pending')->count(),
            'rejected' => SellerStore::query()->where('approval_status', 'rejected')->count(),
        ];

        $stores = SellerStore::query()
            ->with(['user', 'province', 'city'])
            ->when($status === 'active', fn (Builder $query) => $query->where('is_enabled', true)->where('is_admin_disabled', false))
            ->when($status === 'disabled', fn (Builder $query) => $query->where('is_enabled', false)->where('is_admin_disabled', false))
            ->when($status === 'blocked', fn (Builder $query) => $query->where('is_admin_disabled', true))
            ->when($request->input('approval_status') === 'pending', fn (Builder $query) => $query->where('approval_status', 'pending'))
            ->when($request->input('approval_status') === 'rejected', fn (Builder $query) => $query->where('approval_status', 'rejected'))
            ->when($searchLikes !== [], function (Builder $query) use ($searchLikes): void {
                $query->where(function (Builder $search) use ($searchLikes): void {
                    foreach ($searchLikes as $like) {
                        $search->orWhere('name', 'like', $like)
                            ->orWhere('slug', 'like', $like)
                            ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $like)->orWhere('mobile', 'like', $like));
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.storefronts.index', compact('stores', 'storeStats'));
    }

    public function show(SellerStore $sellerStore): View
    {
        $sellerStore->load(['user', 'province', 'city']);

        return view('admin.storefronts.show', [
            'store' => $sellerStore,
            'user' => $sellerStore->user,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.storefronts.edit', [
            'user' => $user,
            'store' => $user->storefront,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function toggle(Request $request, SellerStore $sellerStore): RedirectResponse|JsonResponse
    {
        $sellerStore->update(['is_admin_disabled' => ! $sellerStore->is_admin_disabled]);
        $message = $sellerStore->is_admin_disabled ? 'فروشگاه توسط مدیریت غیرفعال شد.' : 'فروشگاه از حالت غیرفعال مدیریتی خارج شد.';

        if ($request->expectsJson()) {
            $statusLabel = $sellerStore->is_admin_disabled
                ? 'مسدود توسط مدیریت'
                : match ($sellerStore->approval_status) {
                    'pending' => 'در انتظار بررسی',
                    'rejected' => 'ردشده',
                    default => $sellerStore->is_enabled ? 'فعال' : 'غیرفعال',
                };

            return response()->json([
                'message' => $message,
                'is_admin_disabled' => $sellerStore->is_admin_disabled,
                'status_label' => $statusLabel,
            ]);
        }

        return back()->with('status', $message);
    }

    public function approve(Request $request, SellerStore $sellerStore): RedirectResponse|JsonResponse
    {
        $sellerStore->update([
            'approval_status' => 'approved',
            'is_enabled' => true,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $sellerStore->user?->notify(new StorefrontStatusNotification($sellerStore, 'approved'));
        $message = 'فروشگاه با موفقیت تأیید و فعال شد.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status_label' => 'فعال',
                'approval_status' => $sellerStore->approval_status,
                'is_enabled' => $sellerStore->is_enabled,
            ]);
        }

        return back()->with('status', $message);
    }

    public function reject(Request $request, SellerStore $sellerStore): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        $sellerStore->update([
            'approval_status' => 'rejected',
            'is_enabled' => false,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $sellerStore->user?->notify(new StorefrontStatusNotification($sellerStore, 'rejected', $validated['rejection_reason']));
        $message = 'درخواست فروشگاه رد شد و علت برای کاربر ثبت گردید.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status_label' => 'ردشده',
                'approval_status' => $sellerStore->approval_status,
                'is_enabled' => $sellerStore->is_enabled,
            ]);
        }

        return back()->with('status', $message);
    }

    public function update(UpdateStorefrontRequest $request, User $user): RedirectResponse
    {
        $store = $user->storefront;
        $previousApprovalStatus = $store?->approval_status;
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

        $payload = [
            'user_id' => $user->id,
            'name' => $validated['name'],
            'first_name' => $validated['first_name'] ?? $store?->first_name ?? $user->name,
            'last_name' => $validated['last_name'] ?? $store?->last_name,
            'national_id' => $validated['national_id'] ?? $store?->national_id ?? $user->national_id,
            'slug' => $validated['slug'],
            'is_enabled' => (bool) ($validated['store_enabled'] ?? false),
            'is_admin_disabled' => (bool) ($validated['admin_disabled'] ?? false),
            'approval_status' => $validated['approval_status'],
            'province_id' => $validated['province_id'] ?? $store?->province_id,
            'city_id' => $validated['city_id'] ?? $store?->city_id,
            'logo_path' => $logoPath,
            'address' => $validated['address'] ?? $store?->address,
            'contact_phone' => $validated['contact_phone'] ?? $store?->contact_phone ?? $user->mobile,
            'business_license_number' => $validated['business_license_number'] ?? $store?->business_license_number,
            'business_license_expires_at' => filled($validated['business_license_expires_at'] ?? null) ? PersianDate::parseDate($validated['business_license_expires_at'])?->toDateString() : $store?->business_license_expires_at,
            'business_license_path' => $businessLicensePath,
            'instagram_url' => $validated['instagram_url'] ?? null,
            'telegram_url' => $validated['telegram_url'] ?? null,
            'whatsapp_url' => $validated['whatsapp_url'] ?? null,
            'bale_url' => $validated['bale_url'] ?? null,
            'rubika_url' => $validated['rubika_url'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'approved_at' => $validated['approval_status'] === 'approved' ? ($store?->approved_at ?? now()) : null,
            'rejection_reason' => $validated['approval_status'] === 'rejected' ? $validated['rejection_reason'] : null,
        ];

        if ($store) {
            $store->update($payload);
        } else {
            SellerStore::create($payload);
        }

        $store = $store ?: $user->fresh('storefront')->storefront;
        if ($previousApprovalStatus !== $store->approval_status && in_array($store->approval_status, ['approved', 'rejected'], true)) {
            $user->notify(new StorefrontStatusNotification($store, $store->approval_status, $store->rejection_reason));
        }

        return redirect()->route('admin.storefronts.index')->with('status', 'اطلاعات فروشگاه به‌روزرسانی شد.');
    }
}
