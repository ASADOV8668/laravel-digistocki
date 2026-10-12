<?php

namespace App\Http\Controllers\User;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStorefrontRequest;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\PhoneModel;
use App\Models\SellerStore;
use App\Support\PersianDate;
use App\Support\PersianSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Sadegh19b\LaravelIranCities\Models\Province;

class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $randomSeed = $request->integer('random_seed') ?: random_int(1, PHP_INT_MAX);
        $randomOrder = DB::connection()->getDriverName() === 'sqlite' ? 'RANDOM()' : 'RAND(?)';
        $randomBindings = $randomOrder === 'RAND(?)' ? [$randomSeed] : [];
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
            ->orderByRaw($randomOrder, $randomBindings)
            ->paginate(18)
            ->appends([...$request->query(), 'random_seed' => $randomSeed]);

        return view('storefront.index', [
            'stores' => $stores,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'selectedProvince' => $request->input('province_id'),
            'selectedCityIds' => $cityIds->all(),
        ]);
    }

    public function show(Request $request, SellerStore $sellerStore): View|JsonResponse
    {
        $sellerStore->load(['user', 'province', 'city']);

        if (! $sellerStore->isPubliclyEnabled()) {
            return view('storefront.disabled', ['store' => $sellerStore]);
        }

        $storeScope = $this->storeListingQuery($sellerStore);
        $storeBrands = Brand::query()
            ->where('is_active', true)
            ->whereIn('id', (clone $storeScope)->select('brand_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'name_en']);
        $storeModels = PhoneModel::query()
            ->where('is_active', true)
            ->with('brand')
            ->whereIn('id', (clone $storeScope)->select('phone_model_id'))
            ->orderBy('name_fa')
            ->get();

        $selectedBrand = $storeBrands->firstWhere('id', $request->integer('brand_id'));
        $selectedBrandId = $selectedBrand?->id;
        $selectedModel = $storeModels->firstWhere('id', $request->integer('phone_model_id'));
        if ($selectedModel && $selectedBrandId && $selectedModel->brand_id !== $selectedBrandId) {
            $selectedModel = null;
        }
        if ($selectedModel && ! $selectedBrandId) {
            $selectedBrandId = $selectedModel->brand_id;
            $selectedBrand = $storeBrands->firstWhere('id', $selectedBrandId);
        }

        $attributeScope = (clone $storeScope)->when(
            $selectedModel,
            fn (Builder $query) => $query->where('phone_model_id', $selectedModel->id),
        );
        $attributeIds = ListingAttributeValue::query()
            ->whereIn('listing_id', $attributeScope->select('id'))
            ->whereHas('attribute', fn (Builder $query) => $query->where('is_active', true)->where('is_filterable', true))
            ->distinct()
            ->pluck('attribute_id');
        $filterAttributes = Attribute::query()
            ->whereIn('id', $attributeIds)
            ->where('is_active', true)
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->get();

        $search = trim((string) $request->input('q'));
        $searchLikes = PersianSearch::likes($search);
        $query = (clone $storeScope)
            ->when($selectedBrandId, fn (Builder $query) => $query->where('brand_id', $selectedBrandId))
            ->when($selectedModel, fn (Builder $query) => $query->where('phone_model_id', $selectedModel->id))
            ->when($searchLikes !== [], function (Builder $query) use ($searchLikes): void {
                $query->where(function (Builder $listingQuery) use ($searchLikes): void {
                    foreach ($searchLikes as $like) {
                        $listingQuery->orWhere('title', 'like', $like)
                            ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $like)->orWhere('name_en', 'like', $like))
                            ->orWhereHas('phoneModel', fn (Builder $model) => $model->where('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like));
                    }
                });
            })
            ->when($request->filled('min_price'), fn (Builder $query) => $query->where('price', '>=', $request->integer('min_price')))
            ->when($request->filled('max_price'), fn (Builder $query) => $query->where('price', '<=', $request->integer('max_price')));

        $this->applyAttributeFilters($query, $request, $filterAttributes->keyBy('id'));
        $sort = in_array($request->input('sort'), ['newest', 'price_asc', 'price_desc', 'views'], true) ? $request->input('sort') : 'newest';
        match ($sort) {
            'price_asc' => $query->orderByRaw('price IS NULL')->orderBy('price')->latest('published_at'),
            'price_desc' => $query->orderByRaw('price IS NULL')->orderByDesc('price')->latest('published_at'),
            'views' => $query->orderByDesc('views_count')->latest('published_at'),
            default => $query->latest('published_at'),
        };

        $pricedScope = (clone $storeScope)->whereNotNull('price')->where('price_on_request', false);
        $hasPricedListings = (clone $pricedScope)->exists();
        $priceFloor = $hasPricedListings ? (int) (clone $pricedScope)->min('price') : 0;
        $priceCeiling = $hasPricedListings ? (int) (clone $pricedScope)->max('price') : 0;
        if ($hasPricedListings) {
            $priceCeiling = max(1000000, (int) (ceil($priceCeiling / 1000000) * 1000000));
            $priceCeiling = max($priceCeiling, $request->integer('max_price'));
        }

        $activeFilters = $this->activeFilters($request, $sellerStore, $searchLikes, $selectedBrand, $selectedModel, $sort);
        $listings = $query
            ->with(['brand', 'phoneModel', 'province', 'city', 'primaryImage', 'attributeValues.attribute', 'user.storefront'])
            ->withViewerFavorite($request->user()?->id)
            ->paginate(12)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json(['html' => view('storefront.partials.results', compact('listings', 'sort', 'activeFilters', 'sellerStore'))->render()]);
        }

        return view('storefront.show', [
            'store' => $sellerStore,
            'listings' => $listings,
            'brands' => $storeBrands,
            'storeModels' => $storeModels,
            'selectedModel' => $selectedModel,
            'filterAttributesPayload' => $this->attributePayload($filterAttributes),
            'sort' => $sort,
            'activeFilters' => $activeFilters,
            'priceFloor' => $priceFloor,
            'priceCeiling' => $priceCeiling,
            'hasPricedListings' => $hasPricedListings,
        ]);
    }

    public function searchSuggestions(Request $request, SellerStore $sellerStore): JsonResponse
    {
        abort_unless($sellerStore->isPubliclyEnabled(), 404);
        $likes = PersianSearch::likes((string) $request->input('q'));
        if ($likes === []) {
            return response()->json(['brands' => [], 'models' => []]);
        }

        $scope = $this->storeListingQuery($sellerStore);
        $brands = Brand::query()
            ->where('is_active', true)
            ->whereIn('id', (clone $scope)->select('brand_id'))
            ->where(function (Builder $query) use ($likes): void {
                foreach ($likes as $like) {
                    $query->orWhere('name', 'like', $like)->orWhere('name_en', 'like', $like);
                }
            })
            ->orderBy('name')->limit(6)->get();
        $models = PhoneModel::query()
            ->where('is_active', true)->with('brand')
            ->whereIn('id', (clone $scope)->select('phone_model_id'))
            ->where(function (Builder $query) use ($likes): void {
                foreach ($likes as $like) {
                    $query->orWhere('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like);
                }
            })
            ->orderBy('name_fa')->limit(12)->get();

        return response()->json([
            'brands' => $brands->map(fn (Brand $brand) => ['id' => $brand->id, 'label' => $brand->name, 'secondary' => $brand->name_en, 'type' => 'brand'])->values(),
            'models' => $models->map(fn (PhoneModel $model) => ['id' => $model->id, 'brand_id' => $model->brand_id, 'label' => $model->name_fa ?: $model->name, 'secondary' => $model->name_en ?: $model->name, 'brand' => $model->brand?->name, 'type' => 'model'])->values(),
        ]);
    }

    public function models(Request $request, SellerStore $sellerStore): JsonResponse
    {
        abort_unless($sellerStore->isPubliclyEnabled(), 404);
        $likes = PersianSearch::likes((string) $request->input('q'));
        $models = PhoneModel::query()
            ->where('is_active', true)->with('brand')
            ->whereIn('id', $this->storeListingQuery($sellerStore)->select('phone_model_id'))
            ->when($request->integer('brand_id'), fn (Builder $query, int $brandId) => $query->where('brand_id', $brandId))
            ->when($likes !== [], function (Builder $query) use ($likes): void {
                $query->where(function (Builder $query) use ($likes): void {
                    foreach ($likes as $like) {
                        $query->orWhere('name', 'like', $like)->orWhere('name_fa', 'like', $like)->orWhere('name_en', 'like', $like);
                    }
                });
            })
            ->orderBy('name_fa')->get();

        return response()->json($models->map(fn (PhoneModel $model) => [
            'id' => $model->id, 'brand_id' => $model->brand_id,
            'label' => $model->name_fa ?: $model->name, 'secondary' => $model->name_en ?: $model->name,
            'brand' => $model->brand?->name,
        ])->values());
    }

    public function modelAttributes(Request $request, SellerStore $sellerStore, PhoneModel $phoneModel): JsonResponse
    {
        abort_unless($sellerStore->isPubliclyEnabled(), 404);
        $attributeIds = ListingAttributeValue::query()
            ->whereIn('listing_id', $this->storeListingQuery($sellerStore)->where('phone_model_id', $phoneModel->id)->select('id'))
            ->distinct()->pluck('attribute_id');
        $attributes = Attribute::query()->whereIn('id', $attributeIds)->where('is_active', true)
            ->when(! $request->boolean('all'), fn (Builder $query) => $query->where('is_filterable', true))
            ->orderBy('sort_order')->get();

        return response()->json($this->attributePayload($attributes));
    }

    private function storeListingQuery(SellerStore $store): Builder
    {
        return Listing::query()->published()->where('user_id', $store->user_id);
    }

    private function attributePayload($attributes): array
    {
        return $attributes->map(fn (Attribute $attribute) => [
            'id' => $attribute->id, 'name' => $attribute->name, 'slug' => $attribute->slug,
            'type' => $attribute->type->value, 'unit' => $attribute->unit, 'options' => $attribute->options ?? [],
        ])->values()->all();
    }

    private function applyAttributeFilters(Builder $query, Request $request, $filterAttributesById): void
    {
        foreach ((array) $request->input('filters', []) as $attributeId => $value) {
            $attribute = $filterAttributesById->get((int) $attributeId);
            $values = array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));
            if (! $attribute || $values === []) {
                continue;
            }
            $query->whereHas('attributeValues', function (Builder $query) use ($attribute, $values): void {
                $query->where('attribute_id', $attribute->id);
                if ($attribute->type === AttributeType::MultiSelect) {
                    foreach ($values as $value) {
                        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                            $query->whereRaw('JSON_SEARCH(value_json, \'one\', ?) IS NOT NULL', [$value]);
                        } else {
                            $query->whereJsonContains('value_json', $value);
                        }
                    }

                    return;
                }
                $value = $values[0];
                match ($attribute->type) {
                    AttributeType::Integer => $query->where('value_integer', (int) $value),
                    AttributeType::Decimal => $query->where('value_decimal', (float) $value),
                    AttributeType::Boolean => $query->where('value_boolean', filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                    default => $query->where('value_string', $value),
                };
            });
        }
    }

    private function activeFilters(Request $request, SellerStore $store, array $searchLikes, ?Brand $brand, ?PhoneModel $model, string $sort): array
    {
        $without = fn (array $keys): string => route('storefront.show', $store).(empty(Arr::except($request->query(), $keys)) ? '' : '?'.http_build_query(Arr::except($request->query(), $keys)));
        $filters = [];
        if ($searchLikes !== []) {
            $filters[] = ['label' => 'جستجو: '.trim((string) $request->input('q')), 'url' => $without(['q'])];
        }
        if ($brand) {
            $filters[] = ['label' => 'برند: '.$brand->name, 'url' => $without(['brand_id'])];
        }
        if ($model) {
            $filters[] = ['label' => 'مدل: '.($model->name_fa ?: $model->name), 'url' => $without(['phone_model_id', 'filters'])];
        }
        if ($request->filled('min_price')) {
            $filters[] = ['label' => 'حداقل: '.number_format((int) $request->input('min_price')), 'url' => $without(['min_price'])];
        }
        if ($request->filled('max_price')) {
            $filters[] = ['label' => 'حداکثر: '.number_format((int) $request->input('max_price')), 'url' => $without(['max_price'])];
        }
        $filterCount = collect((array) $request->input('filters'))->filter(fn ($value) => collect((array) $value)->contains(fn ($item) => $item !== null && $item !== ''))->count();
        if ($filterCount > 0) {
            $filters[] = ['label' => 'ویژگی‌ها: '.$filterCount.' مورد', 'url' => $without(['filters'])];
        }
        if ($sort !== 'newest') {
            $filters[] = ['label' => 'مرتب‌سازی: '.(['price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین', 'views' => 'پربازدیدترین'][$sort] ?? $sort), 'url' => $without(['sort'])];
        }

        return $filters;
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
