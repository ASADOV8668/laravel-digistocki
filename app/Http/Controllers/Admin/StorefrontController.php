<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStorefrontRequest;
use App\Models\Listing;
use App\Models\SellerStore;
use App\Models\User;
use App\Notifications\StorefrontStatusNotification;
use App\Services\ImageService;
use App\Services\NotificationReadService;
use App\Services\SystemOptions;
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
    use Concerns\InteractsWithAdminPagination;

    public function index(Request $request): View
    {
        $perPage = $this->adminPerPage($request);
        $perPageOptions = $this->adminPerPageOptions();
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
            ->with([
                'user' => fn ($query) => $query->withCount('listings'),
                'province',
                'city',
            ])
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
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.storefronts.index', compact('stores', 'storeStats', 'perPageOptions'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $query = trim((string) $request->input('q'));
        $users = User::query()
            ->whereDoesntHave('storefront')
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $like = '%'.$query.'%';
                $builder->where(fn (Builder $search) => $search
                    ->where('name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name', 'mobile', 'email']);

        if ($request->filled('user_id')) {
            $validated = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
            $user = User::query()->findOrFail($validated['user_id']);

            if ($user->storefront()->exists()) {
                return back()->withErrors(['user_id' => 'این کاربر قبلاً یک فروشگاه دارد.']);
            }

            return redirect()->route('admin.storefronts.edit', $user);
        }

        return view('admin.storefronts.create', compact('users', 'query'));
    }

    public function show(SellerStore $sellerStore, NotificationReadService $notificationReadService): View
    {
        $notificationReadService->markTargetAsRead(auth()->user(), 'store_id', $sellerStore->getKey());
        $sellerStore->load(['user', 'province', 'city']);

        return view('admin.storefronts.show', [
            'store' => $sellerStore,
            'user' => $sellerStore->user,
        ]);
    }

    public function destroy(Request $request, SellerStore $sellerStore): RedirectResponse|JsonResponse
    {
        if (! $sellerStore->isRejected()) {
            return $this->deleteFailureResponse($request, 'فقط فروشگاه‌های ردشده قابل حذف کامل هستند.');
        }

        if (Listing::query()->where('user_id', $sellerStore->user_id)->exists()) {
            return $this->deleteFailureResponse($request, 'این فروشگاه به آگهی متصل است و تا حذف آگهی‌ها قابل حذف نیست.');
        }

        $sellerStore->delete();
        $message = 'فروشگاه ردشده با موفقیت حذف شد و تصاویر آن نیز از هاست پاک شدند.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('admin.storefronts.index')->with('status', $message);
    }

    private function deleteFailureResponse(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['storefront' => $message]);
    }

    public function edit(User $user, SystemOptions $options, NotificationReadService $notificationReadService): View
    {
        $notificationReadService->markTargetAsRead(auth()->user(), 'user_id', $user->getKey());

        return view('admin.storefronts.edit', [
            'user' => $user,
            'store' => $user->storefront,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'maxImageUploadMb' => $options->maxImageUploadMb(),
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

    public function update(UpdateStorefrontRequest $request, User $user, ImageService $imageService): RedirectResponse
    {
        $store = $user->storefront;
        $previousApprovalStatus = $store?->approval_status;
        $validated = $request->validated();
        $logoPath = $store?->logo_path;
        $businessLicensePath = $store?->business_license_path;

        if ($request->hasFile('logo')) {
            $logoPath = $imageService->store($request->file('logo'), 'stores', false)['path'];
            if ($store?->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }
        } elseif ($store?->logo_path && $request->boolean('remove_logo')) {
            Storage::disk('public')->delete($store->logo_path);
            $logoPath = null;
        }

        if ($request->hasFile('business_license')) {
            $businessLicensePath = $imageService->store($request->file('business_license'), 'business-licenses', false)['path'];
            if ($store?->business_license_path) {
                Storage::disk('public')->delete($store->business_license_path);
            }
        } elseif ($store?->business_license_path && $request->boolean('remove_business_license')) {
            Storage::disk('public')->delete($store->business_license_path);
            $businessLicensePath = null;
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
            'listing_approval_mode' => $validated['listing_approval_mode'] ?? ($store?->listing_approval_mode ?? SellerStore::LISTING_APPROVAL_MANUAL),
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
