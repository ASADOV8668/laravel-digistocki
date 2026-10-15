<?php

namespace App\Http\Controllers\User;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\ListingImage;
use App\Models\PhoneModel;
use App\Models\User;
use App\Notifications\PendingListingNotification;
use App\Services\ContactOtpService;
use App\Services\ImageService;
use App\Services\ListingAttributeOptionRegistrar;
use App\Services\ListingFilterOptionService;
use App\Services\ListingRules;
use App\Services\LocationResolver;
use App\Services\NotificationReadService;
use App\Services\SystemOptions;
use App\Support\PersianNumber;
use App\Support\PersianSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;
use Throwable;

class ListingController extends Controller
{
    public function autocomplete(Request $request)
    {
        $rawTerm = trim((string) $request->input('q'));
        $term = $this->normalizeSearchTerm($rawTerm);

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $likes = $this->searchLikes($rawTerm);
        $listings = Listing::query()
            ->published()
            ->with(['brand', 'phoneModel', 'primaryImage'])
            ->where(function (Builder $query) use ($likes) {
                $query->where(function (Builder $title) use ($likes) {
                    foreach ($likes as $like) {
                        $title->orWhere('title', 'like', $like);
                    }
                })->orWhereHas('brand', fn (Builder $brand) => $this->whereAnyLike($brand, ['name', 'name_en'], $likes))
                    ->orWhereHas('phoneModel', fn (Builder $model) => $this->whereAnyLike($model, ['name', 'name_fa', 'name_en'], $likes));
            })
            ->latest('published_at')
            ->limit(8)
            ->get();

        return response()->json($listings->map(fn (Listing $listing) => [
            'title' => $listing->title,
            'meta' => $listing->brand->name.' · '.($listing->phoneModel->name_fa ?: $listing->phoneModel->name),
            'url' => route('listings.show', $listing),
            'image' => $listing->primaryImage ? asset('storage/'.$listing->primaryImage->path) : asset('images/listing-placeholder.svg'),
        ])->values());
    }

    public function searchSuggestions(Request $request)
    {
        $rawTerm = trim((string) $request->input('q'));
        $term = $this->normalizeSearchTerm($rawTerm);

        if (mb_strlen($term) < 2) {
            return response()->json(['brands' => [], 'models' => []]);
        }

        $likes = $this->searchLikes($rawTerm);
        $activeOnly = $request->input('active_only', '1') !== '0';
        $visibleListings = fn () => Listing::query()
            ->approved()
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($activeOnly, fn (Builder $query) => $query->where('is_user_disabled', false));

        $brands = Brand::query()
            ->where('is_active', true)
            ->whereIn('id', $visibleListings()->select('brand_id'))
            ->where(fn (Builder $query) => $this->whereAnyLike($query, ['name', 'name_en'], $likes))
            ->orderBy('name')
            ->limit(6)
            ->get();

        $models = PhoneModel::query()
            ->where('is_active', true)
            ->whereIn('id', $visibleListings()->select('phone_model_id'))
            ->with('brand')
            ->where(function (Builder $query) use ($likes) {
                $query->where(fn (Builder $model) => $this->whereAnyLike($model, ['name', 'name_fa', 'name_en'], $likes))
                    ->orWhereHas('brand', fn (Builder $brand) => $this->whereAnyLike($brand, ['name', 'name_en'], $likes));
            })
            ->orderBy('name_fa')
            ->limit(12)
            ->get();

        return response()->json([
            'brands' => $brands->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'label' => $brand->name,
                'secondary' => $brand->name_en,
                'type' => 'brand',
            ])->values(),
            'models' => $models->map(fn (PhoneModel $model) => [
                'id' => $model->id,
                'brand_id' => $model->brand_id,
                'label' => $model->name_fa ?: $model->name,
                'secondary' => $model->name_en ?: $model->name,
                'brand' => $model->brand?->name,
                'type' => 'model',
            ])->values(),
        ]);
    }

    public function modelAttributes(Request $request, PhoneModel $phoneModel, ListingFilterOptionService $filterOptions)
    {
        abort_unless($phoneModel->is_active, 404);

        $attributes = $phoneModel->attributes()
            ->where('attributes.is_active', true)
            ->when(! $request->boolean('all'), fn ($query) => $query->where('attributes.is_filterable', true))
            ->orderBy('attributes.sort_order')
            ->get();

        $activeOnly = $request->input('active_only', '1') !== '0';
        $scope = Listing::query()
            ->approved()
            ->where('phone_model_id', $phoneModel->id)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($activeOnly, fn (Builder $query) => $query->where('is_user_disabled', false));

        return response()->json($filterOptions->payload(
            $attributes,
            $scope,
            (array) $request->input('filters', []),
            ! $request->boolean('all'),
        ));
    }

    public function models(Request $request)
    {
        $likes = $this->searchLikes((string) $request->input('q'));

        $models = PhoneModel::query()
            ->where('is_active', true)
            ->with('brand')
            ->when($request->input('scope') === 'search', function (Builder $query) use ($request): void {
                $activeOnly = $request->input('active_only', '1') !== '0';
                $visibleListings = Listing::query()
                    ->approved()
                    ->where(function (Builder $listingQuery): void {
                        $listingQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->when($activeOnly, fn (Builder $listingQuery) => $listingQuery->where('is_user_disabled', false));

                $query->whereIn('id', $visibleListings->select('phone_model_id'));
            })
            ->when($request->integer('brand_id'), fn (Builder $query, int $brandId) => $query->where('brand_id', $brandId))
            ->when($likes !== [], function (Builder $query) use ($likes) {
                $query->where(function (Builder $query) use ($likes) {
                    $query->where(fn (Builder $model) => $this->whereAnyLike($model, ['name', 'name_fa', 'name_en'], $likes))
                        ->orWhereHas('brand', fn (Builder $brand) => $this->whereAnyLike($brand, ['name', 'name_en'], $likes));
                });
            })
            ->orderBy('name_fa')
            ->get();

        return response()->json($models->map(fn (PhoneModel $model) => [
            'id' => $model->id,
            'brand_id' => $model->brand_id,
            'label' => $model->name_fa ?: $model->name,
            'secondary' => $model->name_en ?: $model->name,
            'brand' => $model->brand?->name,
        ])->values());
    }

    public function cities(Province $province)
    {
        return response()->json($province->cities()->orderBy('name')->get(['id', 'name', 'province_id']));
    }

    public function reverseLocation(Request $request, LocationResolver $resolver)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $location = $resolver->nearest((float) $validated['latitude'], (float) $validated['longitude']);
        if (! $location) {
            return response()->json(['message' => 'برای استان‌ها و شهرها مختصات ثبت نشده است.'], 422);
        }

        return response()->json($location);
    }

    public function index(Request $request)
    {
        $selectedBrandId = $request->integer('brand_id');
        $selectedModelId = $request->integer('phone_model_id');
        $selectedModel = $request->integer('phone_model_id')
            ? PhoneModel::query()
                ->where('is_active', true)
                ->when($selectedBrandId, fn (Builder $query) => $query->where('brand_id', $selectedBrandId))
                ->with(['attributes' => fn ($query) => $query->where('is_active', true)->where('is_filterable', true)->orderBy('sort_order')])
                ->find($selectedModelId)
            : null;
        $filterAttributes = $selectedModel?->attributes ?? collect();
        $filterAttributesById = $filterAttributes->keyBy('id');
        $activeOnly = $request->input('active_only', '1') !== '0';
        $searchTerm = trim((string) $request->input('q'));
        $searchLikes = $this->searchLikes($searchTerm);
        $query = Listing::query()
            ->approved()
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($activeOnly, fn (Builder $query) => $query->where('is_user_disabled', false))
            ->with(['brand', 'phoneModel', 'province', 'city', 'primaryImage', 'images', 'attributeValues.attribute', 'user.storefront'])
            ->withViewerFavorite(auth()->id());

        $query->when($selectedBrandId, fn (Builder $query) => $query->where('brand_id', $selectedBrandId));
        $query->when($selectedModelId, fn (Builder $query) => $query->where('phone_model_id', $selectedModelId));
        $query->when($request->integer('province_id'), fn (Builder $query, int $provinceId) => $query->where('province_id', $provinceId));
        $selectedCityId = $request->integer('city_id');
        $cityIds = collect((array) $request->input('city_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $query->when($cityIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('city_id', $cityIds->all()))
            ->when($cityIds->isEmpty() && $selectedCityId, fn (Builder $query) => $query->where('city_id', $selectedCityId));
        $query->when($request->integer('min_price'), fn (Builder $query, int $price) => $query->where('price', '>=', $price));
        $query->when($request->integer('max_price'), fn (Builder $query, int $price) => $query->where('price', '<=', $price));
        $query->when($searchLikes !== [], function (Builder $query) use ($searchLikes) {
            $query->where(function (Builder $query) use ($searchLikes) {
                $query->where(function (Builder $title) use ($searchLikes) {
                    foreach ($searchLikes as $like) {
                        $title->orWhere('title', 'like', $like);
                    }
                })->orWhereHas('brand', fn (Builder $brand) => $this->whereAnyLike($brand, ['name', 'name_en'], $searchLikes))
                    ->orWhereHas('phoneModel', fn (Builder $model) => $this->whereAnyLike($model, ['name', 'name_fa', 'name_en'], $searchLikes));
            });
        });

        foreach ((array) $request->input('filters', []) as $attributeId => $value) {
            $attribute = $filterAttributesById->get((int) $attributeId);
            $values = array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));
            if (! $attribute || $values === []) {
                continue;
            }

            $query->whereHas('attributeValues', function (Builder $query) use ($attribute, $values) {
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
                    default => $query->where(function (Builder $query) use ($value): void {
                        $query->where('value_string', (string) $value);

                        // Support values saved before a catalog attribute changed
                        // from numeric to select/string.
                        if (is_numeric($value)) {
                            $query->orWhere('value_integer', (int) $value)
                                ->orWhere('value_decimal', (float) $value);
                        }
                    }),
                };
            });
        }

        $sort = in_array($request->input('sort'), ['newest', 'price_asc', 'price_desc', 'views'], true)
            ? $request->input('sort')
            : 'newest';

        $query->orderByRaw('CASE WHEN is_user_disabled = 0 THEN 0 ELSE 1 END');
        match ($sort) {
            'price_asc' => $query->orderByRaw('price IS NULL')->orderBy('price')->latest('published_at'),
            'price_desc' => $query->orderByRaw('price IS NULL')->orderByDesc('price')->latest('published_at'),
            'views' => $query->orderByDesc('views_count')->latest('published_at'),
            default => $query->latest('published_at'),
        };

        $selectedBrand = $selectedBrandId ? Brand::query()->where('is_active', true)->find($selectedBrandId) : null;
        $selectedProvince = $request->integer('province_id') ? Province::query()->find($request->integer('province_id')) : null;
        $selectedCity = $cityIds->isEmpty() && $request->integer('city_id') ? City::query()->find($request->integer('city_id')) : null;
        $selectedCities = $cityIds->isNotEmpty() ? City::query()->whereIn('id', $cityIds)->orderBy('name')->get() : collect();
        $without = fn (array $keys): string => route('listings.index', Arr::except($request->query(), $keys));
        $activeFilters = [];

        if ($searchLikes !== []) {
            $activeFilters[] = ['label' => 'جستجو: '.trim((string) $request->input('q')), 'url' => $without(['q'])];
        }
        if ($selectedBrand) {
            $activeFilters[] = ['label' => 'برند: '.$selectedBrand->name, 'url' => $without(['brand_id'])];
        }
        if ($selectedModel) {
            $activeFilters[] = ['label' => 'مدل: '.($selectedModel->name_fa ?: $selectedModel->name), 'url' => $without(['phone_model_id', 'filters'])];
        }
        if ($selectedProvince) {
            $activeFilters[] = ['label' => 'استان: '.$selectedProvince->name, 'url' => $without(['province_id', 'city_id'])];
        }
        if ($selectedCity) {
            $activeFilters[] = ['label' => 'شهر: '.$selectedCity->name, 'url' => $without(['city_id'])];
        }
        if ($selectedCities->isNotEmpty()) {
            $activeFilters[] = ['label' => 'شهرها: '.$selectedCities->pluck('name')->join('، '), 'url' => $without(['city_ids'])];
        }
        if ($request->filled('min_price')) {
            $activeFilters[] = ['label' => 'حداقل: '.number_format((int) $request->input('min_price')), 'url' => $without(['min_price'])];
        }
        if ($request->filled('max_price')) {
            $activeFilters[] = ['label' => 'حداکثر: '.number_format((int) $request->input('max_price')), 'url' => $without(['max_price'])];
        }
        if ($request->filled('filters')) {
            $filterCount = collect((array) $request->input('filters'))
                ->filter(fn ($value) => collect((array) $value)->contains(fn ($item) => $item !== null && $item !== ''))
                ->count();
            if ($filterCount > 0) {
                $activeFilters[] = ['label' => 'ویژگی‌ها: '.$filterCount.' مورد', 'url' => $without(['filters'])];
            }
        }
        if ($sort !== 'newest') {
            $sortLabels = ['price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین', 'views' => 'پربازدیدترین'];
            $activeFilters[] = ['label' => 'مرتب‌سازی: '.$sortLabels[$sort], 'url' => $without(['sort'])];
        }

        $filterOptionScope = Listing::query()
            ->approved()
            ->where(function (Builder $scopeQuery): void {
                $scopeQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($activeOnly, fn (Builder $scopeQuery) => $scopeQuery->where('is_user_disabled', false))
            ->when($selectedModelId, fn (Builder $scopeQuery) => $scopeQuery->where('phone_model_id', $selectedModelId));
        $filterAttributesPayload = app(ListingFilterOptionService::class)->payload(
            $filterAttributes,
            $filterOptionScope,
            (array) $request->input('filters', []),
        );

        $options = app(SystemOptions::class);
        $paginationMode = $options->listingsPaginationType();
        $listings = $query->paginate($options->searchListingsPerPage())->withQueryString();
        $pricedListings = Listing::query()
            ->approved()
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->when($activeOnly, fn (Builder $query) => $query->where('is_user_disabled', false))
            ->whereNotNull('price')
            ->where('price_on_request', false);
        $priceFloor = (int) (clone $pricedListings)->min('price');
        $rawPriceCeiling = (int) (clone $pricedListings)->max('price');
        $hasPriceRange = $rawPriceCeiling > $priceFloor;
        $priceCeiling = $rawPriceCeiling > 0
            ? max(1000000, (int) (ceil($rawPriceCeiling / 1000000) * 1000000))
            : 1000000;
        $priceCeiling = max($priceCeiling, (int) $request->integer('max_price'));

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view(
                    $request->boolean('infinite') ? 'listings.partials.cards' : 'listings.partials.results',
                    $request->boolean('infinite')
                        ? compact('listings')
                        : compact('listings', 'sort', 'activeFilters', 'paginationMode'),
                )->render(),
                'next_page_url' => $listings->nextPageUrl(),
                'has_more' => $listings->hasMorePages(),
            ]);
        }

        return view('listings.index', [
            'listings' => $listings,
            'brands' => Brand::query()->where('is_active', true)->with(['phoneModels' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'attributes' => Attribute::query()->where('is_active', true)->where('is_filterable', true)->orderBy('sort_order')->get(),
            'selectedModel' => $selectedModel,
            'filterAttributesPayload' => $filterAttributesPayload,
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'sort' => $sort,
            'activeFilters' => $activeFilters,
            'hasPriceRange' => $hasPriceRange,
            'activeOnly' => $activeOnly,
            'priceCeiling' => $priceCeiling,
            'selectedCityIds' => $cityIds->all(),
            'paginationMode' => $paginationMode,
        ]);
    }

    public function show(Request $request, Listing $listing, NotificationReadService $notificationReadService)
    {
        $this->authorize('view', $listing);
        if ($request->user()) {
            $notificationReadService->markTargetAsRead($request->user(), 'listing_id', $listing->getKey());
        }
        $listing->increment('views_count');
        $listing->load(['brand', 'phoneModel', 'province', 'city', 'user.storefront', 'images', 'attributeValues.attribute']);

        $attributePairs = $listing->attributeValues->map(fn ($value) => [(int) $value->attribute_id, $value->value_string ?? $value->value_integer ?? $value->value_decimal ?? $value->value_boolean])->values();
        $relatedListings = Listing::query()
            ->published()
            ->whereKeyNot($listing->id)
            ->with(['brand', 'phoneModel', 'province', 'city', 'primaryImage', 'attributeValues.attribute', 'user.storefront'])
            ->withViewerFavorite(auth()->id())
            ->where(function (Builder $query) use ($listing, $attributePairs) {
                $query->where('phone_model_id', $listing->phone_model_id)->orWhere('brand_id', $listing->brand_id);
                if ($attributePairs->isNotEmpty()) {
                    $query->orWhereHas('attributeValues', fn (Builder $values) => $values->whereIn('attribute_id', $attributePairs->pluck(0)->all()));
                }
            })
            ->latest('published_at')
            ->limit(12)
            ->get()
            ->sortByDesc(function (Listing $candidate) use ($listing, $attributePairs) {
                $score = $candidate->phone_model_id === $listing->phone_model_id ? 10 : 0;
                $score += $candidate->brand_id === $listing->brand_id ? 4 : 0;
                $score += $candidate->attributeValues->whereIn('attribute_id', $attributePairs->pluck(0))->count();

                return $score;
            })
            ->take(4)
            ->values();

        $revealedContactIds = request()->session()->get('revealed_contact_listings', []);
        $contactRevealed = auth()->check() || in_array($listing->id, $revealedContactIds, true);
        $isFavorited = auth()->check() && $listing->favorites()->where('user_id', auth()->id())->exists();

        return view('listings.show', compact('listing', 'relatedListings', 'contactRevealed', 'isFavorited'));
    }

    public function requestContactOtp(Request $request, Listing $listing, ContactOtpService $otpService)
    {
        $this->authorize('view', $listing);
        abort_if(blank($request->user()->mobile), 422, 'برای دریافت کد تماس، شماره موبایل حساب خود را تکمیل کنید.');
        if (! $otpService->issue($request->user(), $listing)) {
            return back()->withErrors(['contact_otp' => 'پیامک ارسال نشد؛ لطفاً بعداً دوباره تلاش کنید.']);
        }

        $request->session()->put('contact_otp_listing_id', $listing->id);

        return back()->with('status', 'کد تأیید به شماره موبایل شما ارسال شد.');
    }

    public function verifyContactOtp(Request $request, Listing $listing, ContactOtpService $otpService)
    {
        $this->authorize('view', $listing);
        $validated = $request->validate(['otp' => ['required', 'digits_between:5,6']]);
        if (! $otpService->verify($request->user(), $listing, $validated['otp'])) {
            return back()->withErrors(['otp' => 'کد واردشده نادرست یا منقضی شده است.']);
        }

        $revealed = $request->session()->get('revealed_contact_listings', []);
        $request->session()->put('revealed_contact_listings', array_values(array_unique([...$revealed, $listing->id])));
        $request->session()->forget('contact_otp_listing_id');

        return back()->with('status', 'شماره تماس فروشنده برای شما نمایش داده شد.');
    }

    public function create(Request $request, ListingRules $rules, SystemOptions $options)
    {
        if (! $rules->canCreate(auth()->user())) {
            if (! auth()->user()->storefront()->exists()) {
                return redirect()->route('storefront.edit')->with('status', 'برای ثبت آگهی، ابتدا درخواست فروشگاه خود را ثبت کنید.');
            }

            return redirect()->route('home')->with('status', 'در حال حاضر امکان ثبت آگهی برای حساب شما فعال نیست.');
        }
        $cloneListing = $request->filled('clone')
            ? Listing::query()->where('user_id', $request->user()->id)->with(['attributeValues', 'images'])->findOrFail($request->integer('clone'))
            : null;
        $cloneValues = $cloneListing?->attributeValues->mapWithKeys(fn ($value) => [$value->attribute_id => $value->value_json ?? $value->value_string ?? $value->value_integer ?? $value->value_decimal ?? $value->value_boolean])->all() ?? [];
        $store = $request->user()->storefront;
        $selectedModelId = old('phone_model_id', $cloneListing?->phone_model_id);
        $selectedModel = $selectedModelId
            ? PhoneModel::query()->where('is_active', true)->with(['attributes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])->find($selectedModelId)
            : null;

        return view('listings.create', [
            'brands' => Brand::query()->where('is_active', true)->with(['phoneModels' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'attributes' => Attribute::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'provinces' => Province::query()->orderBy('name')->get(),
            'initialModelAttributes' => $this->attributePayload($selectedModel?->attributes ?? collect()),
            'initialValues' => old('attributes', $cloneValues),
            'cloneListing' => $cloneListing,
            'store' => $store,
            'maxImageUploadMb' => $options->maxImageUploadMb(),
            'maxImageUploadCount' => $options->maxImageUploadCount(),
            'allowContactPrice' => $options->allowContactPrice(),
        ]);
    }

    public function edit(Listing $listing, SystemOptions $options)
    {
        $this->authorize('update', $listing);
        $listing->load(['images', 'attributeValues', 'phoneModel', 'user.storefront']);
        $attributes = $listing->phoneModel->attributes()
            ->where('attributes.is_active', true)
            ->orderBy('attributes.sort_order')
            ->get();

        return view('listings.edit', [
            'listing' => $listing,
            'store' => $listing->user?->storefront,
            'brands' => Brand::query()->where('is_active', true)->with(['phoneModels' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'attributes' => $attributes,
            'provinces' => Province::query()->orderBy('name')->get(),
            'initialValues' => $listing->attributeValues->mapWithKeys(fn ($value) => [$value->attribute_id => $value->value_json ?? $value->value_string ?? $value->value_integer ?? $value->value_decimal ?? $value->value_boolean])->all(),
            'initialModelAttributes' => $this->attributePayload($attributes),
            'maxImageUploadMb' => $options->maxImageUploadMb(),
            'maxImageUploadCount' => $options->maxImageUploadCount(),
            'allowContactPrice' => $options->allowContactPrice(),
        ]);
    }

    public function store(StoreListingRequest $request, ListingRules $rules, ImageService $imageService, SystemOptions $options)
    {
        $validated = $request->validated();
        $brand = Brand::query()->whereKey($validated['brand_id'])->where('is_active', true)->firstOrFail();
        $model = PhoneModel::query()->whereKey($validated['phone_model_id'])->where('brand_id', $brand->id)->where('is_active', true)->firstOrFail();

        if ($rules->hasRecentDuplicate($request->user(), $brand->id, $model->id)) {
            throw ValidationException::withMessages([
                'phone_model_id' => 'برای این مدل در '.PersianNumber::digits($options->listingDuplicateCooldownMinutes()).' دقیقه گذشته آگهی ثبت کرده‌اید.',
            ]);
        }

        $storedFiles = [];
        $autoPublish = $request->user()->storefront?->allowsFreeListing() ?? false;
        try {
            $listing = DB::transaction(function () use ($validated, $request, $model, $imageService, $options, $rules, $autoPublish, &$storedFiles) {
                $listingData = $validated;
                unset($listingData['attributes'], $listingData['images'], $listingData['primary_image_index'], $listingData['primary_image_id']);
                $listing = Listing::create([
                    ...$listingData,
                    'user_id' => $request->user()->id,
                    'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(8)),
                    'status' => $autoPublish ? 'approved' : 'pending',
                    'published_at' => $autoPublish ? now() : null,
                    'expires_at' => $autoPublish ? $rules->expiryDate() : null,
                ]);

                $this->saveAttributeValues($listing, $model, (array) ($validated['attributes'] ?? []));
                $uploads = (array) ($validated['images'] ?? []);
                $this->storeImages($listing, $uploads, $imageService, $storedFiles, $validated['primary_image_index'] ?? null, $validated['primary_image_id'] ?? null);
                if ($uploads === [] && $request->integer('clone')) {
                    $this->cloneImages($listing, $request->integer('clone'), $options, $storedFiles);
                }

                return $listing;
            });
        } catch (Throwable $exception) {
            $imageService->deleteMany($storedFiles);

            throw $exception;
        }

        if ($listing->status->value === 'pending') {
            User::query()->where('role', 'admin')->get()->each(
                fn (User $admin) => $admin->notify(new PendingListingNotification($listing)),
            );
        }

        return redirect()->route('listings.show', $listing)->with('status', $autoPublish ? 'آگهی شما به‌صورت خودکار منتشر شد.' : 'آگهی شما برای بررسی ارسال شد.');
    }

    public function quickUpdate(Request $request, Listing $listing, SystemOptions $options): RedirectResponse|JsonResponse
    {
        abort_unless($listing->isOwnedBy($request->user()) || $request->user()->isAdmin(), 403);
        $validated = $request->validate(['price_on_request' => ['nullable', 'boolean'], 'price' => ['nullable', 'integer', 'min:0']]);
        $onRequest = $request->boolean('price_on_request');
        if ($onRequest && ! $options->allowContactPrice()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'امکان «تماس بگیرید» در تنظیمات سیستم فعال نیست.'], 422);
            }

            return back()->withErrors(['price' => 'امکان «تماس بگیرید» در تنظیمات سیستم فعال نیست.']);
        }
        if (! $onRequest && ($validated['price'] ?? null) === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'قیمت را وارد کنید یا گزینه «تماس بگیرید» را انتخاب کنید.'], 422);
            }

            return back()->withErrors(['price' => 'قیمت را وارد کنید یا گزینه «تماس بگیرید» را انتخاب کنید.']);
        }
        $listing->update(['price_on_request' => $onRequest, 'price' => $onRequest ? null : $validated['price']]);
        $message = 'قیمت آگهی به‌سرعت به‌روزرسانی شد.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'price' => $listing->price,
                'price_on_request' => $listing->price_on_request,
            ]);
        }

        return back()->with('status', $message);
    }

    public function toggleDisabled(Request $request, Listing $listing): RedirectResponse|JsonResponse
    {
        abort_unless($listing->isOwnedBy($request->user()) || $request->user()->isAdmin(), 403);
        $listing->update(['is_user_disabled' => ! $listing->is_user_disabled]);
        $message = $listing->is_user_disabled ? 'آگهی غیرفعال شد.' : 'آگهی دوباره فعال شد.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'is_user_disabled' => $listing->is_user_disabled,
            ]);
        }

        return back()->with('status', $message);
    }

    public function bulkAction(Request $request, ImageService $imageService, SystemOptions $options): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:disable,enable,delete,contact_price,price,sold'],
            'listing_ids' => ['required', 'array', 'min:1'],
            'listing_ids.*' => ['integer'],
            'bulk_price' => ['nullable', 'integer', 'min:0', Rule::requiredIf(fn (): bool => $request->input('action') === 'price' && in_array($request->input('bulk_price_mode', 'equal'), ['equal'], true))],
            'bulk_price_mode' => ['nullable', 'in:contact,equal,increase_percent,decrease_percent'],
            'bulk_percent' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_if:bulk_price_mode,increase_percent,decrease_percent'],
        ]);
        $listings = Listing::query()->where('user_id', $request->user()->id)->whereKey($validated['listing_ids'])->with('images')->get();
        $affectedListingIds = $listings->modelKeys();
        if ($validated['action'] === 'delete') {
            $files = $listings->flatMap(fn (Listing $listing) => $listing->images->map(fn (ListingImage $image) => [$image->path, $image->thumbnail_path]));
            $listings->each->delete();
            $imageService->deleteMany($files);
        } elseif ($validated['action'] === 'contact_price') {
            if (! $options->allowContactPrice()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'امکان «تماس بگیرید» در تنظیمات سیستم فعال نیست.'], 422);
                }

                return back()->withErrors(['action' => 'امکان «تماس بگیرید» در تنظیمات سیستم فعال نیست.']);
            }

            $listings->each->update(['price' => null, 'price_on_request' => true]);
        } elseif ($validated['action'] === 'price') {
            $mode = $validated['bulk_price_mode'] ?? 'equal';

            if ($mode === 'contact') {
                if (! $options->allowContactPrice()) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'امکان «تماس بگیرید» در تنظیمات سیستم فعال نیست.'], 422);
                    }

                    return back()->withErrors(['bulk_price_mode' => 'امکان «تماس بگیرید» در تنظیمات سیستم فعال نیست.']);
                }

                $listings->each->update(['price' => null, 'price_on_request' => true]);
            } elseif ($mode === 'equal') {
                $listings->each->update(['price' => (int) $validated['bulk_price'], 'price_on_request' => false]);
            } else {
                $percent = (float) $validated['bulk_percent'];
                $increase = $mode === 'increase_percent';
                $listings->each(function (Listing $listing) use ($percent, $increase): void {
                    if ($listing->price_on_request || $listing->price === null) {
                        return;
                    }

                    $factor = $increase ? 1 + ($percent / 100) : 1 - ($percent / 100);
                    $price = (int) round(($listing->price * $factor) / 1000) * 1000;
                    $listing->update(['price' => max(0, $price), 'price_on_request' => false]);
                });
            }
        } elseif ($validated['action'] === 'sold') {
            $approvedListings = $listings->filter(fn (Listing $listing): bool => $listing->status->value === 'approved');
            $approvedListings->each->update(['status' => 'sold']);
            $affectedListingIds = $approvedListings->modelKeys();
        } else {
            $listings->each->update(['is_user_disabled' => $validated['action'] === 'disable']);
        }

        $messages = [
            'delete' => 'آگهی‌های انتخاب‌شده حذف شدند.',
            'contact_price' => 'قیمت آگهی‌های انتخاب‌شده حذف و حالت «تماس بگیرید» فعال شد.',
            'price' => 'قیمت آگهی‌های انتخاب‌شده به‌روزرسانی شد.',
            'disable' => 'آگهی‌های انتخاب‌شده غیرفعال شدند.',
            'enable' => 'آگهی‌های انتخاب‌شده فعال شدند.',
            'sold' => 'آگهی‌های منتشرشده انتخاب‌شده به‌عنوان فروخته‌شده علامت خوردند.',
        ];
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $messages[$validated['action']],
                'action' => $validated['action'],
                'listing_ids' => array_values($affectedListingIds),
                'price' => $validated['action'] === 'price' && ($validated['bulk_price_mode'] ?? 'equal') === 'equal'
                    ? (int) $validated['bulk_price']
                    : null,
                'price_mode' => $validated['action'] === 'price' ? ($validated['bulk_price_mode'] ?? 'equal') : null,
                'prices' => $validated['action'] === 'price'
                    ? $listings->fresh()->mapWithKeys(fn (Listing $listing) => [$listing->id => [
                        'price' => $listing->price,
                        'price_on_request' => $listing->price_on_request,
                    ]])->all()
                    : [],
            ]);
        }

        return back()->with('status', $messages[$validated['action']]);
    }

    public function update(UpdateListingRequest $request, Listing $listing, ListingRules $rules, ImageService $imageService, SystemOptions $options)
    {
        $validated = $request->validated();
        $brand = Brand::query()->whereKey($validated['brand_id'])->where('is_active', true)->firstOrFail();
        $model = PhoneModel::query()->whereKey($validated['phone_model_id'])->where('brand_id', $brand->id)->where('is_active', true)->firstOrFail();

        if ($rules->hasRecentDuplicate($request->user(), $brand->id, $model->id, null, $listing->id)) {
            throw ValidationException::withMessages([
                'phone_model_id' => 'برای این مدل در '.PersianNumber::digits($options->listingDuplicateCooldownMinutes()).' دقیقه گذشته آگهی دیگری ثبت کرده‌اید.',
            ]);
        }

        $storedFiles = [];
        $autoPublish = $listing->user?->storefront?->allowsFreeListing() ?? false;
        try {
            DB::transaction(function () use ($validated, $listing, $model, $imageService, $rules, $autoPublish, &$storedFiles) {
                $listingData = $validated;
                unset($listingData['attributes'], $listingData['images'], $listingData['primary_image_index'], $listingData['primary_image_id']);
                $listing->update([
                    ...$listingData,
                    'status' => $autoPublish ? 'approved' : 'pending',
                    'rejection_reason' => null,
                    'published_at' => $autoPublish ? now() : null,
                    'expires_at' => $autoPublish ? $rules->expiryDate() : null,
                ]);
                $listing->attributeValues()->delete();
                $this->saveAttributeValues($listing, $model, (array) ($validated['attributes'] ?? []));
                $this->storeImages($listing, (array) ($validated['images'] ?? []), $imageService, $storedFiles, $validated['primary_image_index'] ?? null, $validated['primary_image_id'] ?? null);
            });
        } catch (Throwable $exception) {
            $imageService->deleteMany($storedFiles);

            throw $exception;
        }

        return redirect()->route('listings.show', $listing)->with(
            'status',
            $autoPublish ? 'تغییرات آگهی ذخیره و آگهی منتشر شد.' : 'تغییرات ذخیره و آگهی دوباره برای بررسی ارسال شد.',
        );
    }

    public function destroy(Request $request, Listing $listing, ImageService $imageService)
    {
        $this->authorize('delete', $listing);
        $images = $listing->images()->get(['path', 'thumbnail_path']);
        $listing->delete();
        $imageService->deleteMany($images->map(fn (ListingImage $image) => [$image->path, $image->thumbnail_path]));

        return redirect()->route('dashboard')->with('status', 'آگهی حذف شد.');
    }

    public function markSold(Request $request, Listing $listing)
    {
        $this->authorize('markSold', $listing);
        $listing->forceFill(['status' => 'sold'])->save();

        return back()->with('status', 'آگهی به‌عنوان فروخته‌شده علامت خورد.');
    }

    public function restoreSold(Request $request, Listing $listing, ListingRules $rules)
    {
        $this->authorize('restoreSold', $listing);
        $listing->forceFill([
            'status' => 'approved',
            'is_user_disabled' => false,
            'published_at' => now(),
            'expires_at' => $rules->expiryDate(),
            'rejection_reason' => null,
        ])->save();

        return back()->with('status', 'آگهی دوباره منتشر شد.');
    }

    public function renew(Request $request, Listing $listing, ListingRules $rules)
    {
        $this->authorize('renew', $listing);

        if (! $rules->canRenew($request->user())) {
            return back()->withErrors(['listing' => 'در حال حاضر امکان ارسال مجدد این آگهی وجود ندارد.']);
        }

        $autoPublish = $listing->user?->storefront?->allowsFreeListing() ?? false;
        $listing->forceFill([
            'status' => $autoPublish ? 'approved' : 'pending',
            'published_at' => $autoPublish ? now() : null,
            'expires_at' => $autoPublish ? $rules->expiryDate() : null,
            'rejection_reason' => null,
        ])->save();

        return back()->with('status', $autoPublish ? 'آگهی دوباره منتشر شد.' : 'آگهی برای بررسی مجدد ارسال شد.');
    }

    public function destroyImage(Request $request, Listing $listing, ListingImage $image, ImageService $imageService)
    {
        $this->authorize('update', $listing);
        abort_unless($image->listing_id === $listing->id, 404);
        $wasPrimary = (bool) $image->is_primary;
        $sortOrder = (int) $image->sort_order;
        $imageService->delete($image->path, $image->thumbnail_path);
        $image->delete();

        $replacement = $listing->images()
            ->where('sort_order', '>', $sortOrder)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first()
            ?? $listing->images()->orderBy('sort_order')->orderBy('id')->first();

        if ($wasPrimary) {
            $listing->images()->update(['is_primary' => false]);
        }

        if ($wasPrimary && $replacement) {
            $replacement->update(['is_primary' => true]);
        } elseif (! $listing->images()->where('is_primary', true)->exists() && $replacement) {
            $replacement->update(['is_primary' => true]);
        }

        return back()->with('status', 'تصویر حذف شد.');
    }

    private function saveAttributeValues(Listing $listing, PhoneModel $model, array $values): void
    {
        $attributes = $model->attributes()
            ->where('attributes.is_active', true)
            ->get()
            ->keyBy('id');
        $now = now();
        $payloads = [];

        foreach ($values as $attributeId => $value) {
            $attribute = $attributes->get((int) $attributeId);
            if (! $attribute || $value === null || $value === '' || (is_array($value) && $value === [])) {
                continue;
            }

            app(ListingAttributeOptionRegistrar::class)->remember($attribute, $value);

            $payload = [
                'listing_id' => $listing->id,
                'attribute_id' => $attribute->id,
                'value_string' => null,
                'value_integer' => null,
                'value_decimal' => null,
                'value_boolean' => null,
                'value_json' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            match ($attribute->type) {
                AttributeType::Integer => $payload['value_integer'] = (int) $value,
                AttributeType::Decimal => $payload['value_decimal'] = (float) $value,
                AttributeType::Boolean => $payload['value_boolean'] = filter_var($value, FILTER_VALIDATE_BOOLEAN),
                AttributeType::MultiSelect => $payload['value_json'] = json_encode((array) $value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                default => $payload['value_string'] = (string) $value,
            };
            $payloads[] = $payload;
        }

        if ($payloads !== []) {
            ListingAttributeValue::query()->insert($payloads);
        }
    }

    private function storeImages(Listing $listing, array $images, ImageService $imageService, array &$storedFiles = [], ?int $primaryImageIndex = null, ?int $primaryImageId = null): void
    {
        $uploads = array_values(array_filter($images, fn ($image) => $image instanceof UploadedFile));
        $primaryImageId = filled($primaryImageId) ? (int) $primaryImageId : null;
        $primaryImageIndex = filled($primaryImageIndex) ? (int) $primaryImageIndex : null;
        $selectedPrimary = $primaryImageId !== null
            ? $listing->images()->whereKey($primaryImageId)->first()
            : null;

        if ($uploads === []) {
            // The index belongs only to newly selected files. Never clear an
            // existing primary image just because the edit form submitted its
            // default index while no new file was selected.
            if ($selectedPrimary) {
                $listing->images()->update(['is_primary' => false]);
                $selectedPrimary->update(['is_primary' => true]);
            } elseif (! $listing->images()->where('is_primary', true)->exists()) {
                $listing->images()->orderBy('sort_order')->orderBy('id')->first()?->update(['is_primary' => true]);
            }

            return;
        }

        if ($selectedPrimary || $primaryImageIndex !== null) {
            $listing->images()->update(['is_primary' => false]);
            $selectedPrimary?->update(['is_primary' => true]);
        }

        $hasPrimary = $selectedPrimary !== null || $listing->images()->where('is_primary', true)->exists();
        $nextSortOrder = (int) $listing->images()->max('sort_order') + 1;

        foreach ($uploads as $index => $image) {
            $stored = $imageService->store($image, 'listings/'.$listing->id);
            $storedFiles[] = [$stored['path'] ?? null, $stored['thumbnail_path'] ?? null];
            $isPrimary = $selectedPrimary === null
                ? ($primaryImageIndex !== null ? $index === $primaryImageIndex : ! $hasPrimary)
                : false;
            $listing->images()->create([
                ...$stored,
                'is_primary' => $isPrimary,
                'sort_order' => $nextSortOrder++,
            ]);
            $hasPrimary = $hasPrimary || $isPrimary;
        }
    }

    private function cloneImages(Listing $listing, int $sourceId, SystemOptions $options, array &$storedFiles): void
    {
        $source = Listing::query()->where('user_id', $listing->user_id)->with('images')->find($sourceId);
        if (! $source) {
            return;
        }
        $disk = Storage::disk('public');
        foreach ($source->images->take($options->maxImageUploadCount()) as $sourceImage) {
            $name = (string) Str::uuid();
            $extension = pathinfo($sourceImage->path, PATHINFO_EXTENSION) ?: 'webp';
            $path = 'listings/'.$listing->id.'/'.$name.'.'.$extension;
            $thumbnail = $sourceImage->thumbnail_path ? 'listings/'.$listing->id.'/thumbs/'.$name.'.'.(pathinfo($sourceImage->thumbnail_path, PATHINFO_EXTENSION) ?: 'webp') : null;
            if (! $disk->exists($sourceImage->path) || ! $disk->copy($sourceImage->path, $path)) {
                continue;
            }
            if ($thumbnail && $disk->exists($sourceImage->thumbnail_path)) {
                $disk->copy($sourceImage->thumbnail_path, $thumbnail);
            }
            $listing->images()->create(['path' => $path, 'thumbnail_path' => $thumbnail, 'is_primary' => $sourceImage->is_primary, 'sort_order' => $sourceImage->sort_order]);
            $storedFiles[] = [$path, $thumbnail];
        }
    }

    private function normalizeSearchTerm(string $term): string
    {
        return PersianSearch::normalize($term);
    }

    /** @return array<int, string> */
    private function searchLikes(string $term): array
    {
        $term = trim($term);
        if (mb_strlen($this->normalizeSearchTerm($term)) < 2) {
            return [];
        }

        $variants = array_merge(
            $this->searchVariants($term),
            $this->searchVariants($this->normalizeSearchTerm($term)),
        );

        return collect($variants)
            ->map(fn (string $variant) => trim($variant))
            ->filter(fn (string $variant) => mb_strlen($variant) >= 2)
            ->unique()
            ->map(fn (string $variant) => '%'.$variant.'%')
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function searchVariants(string $term): array
    {
        $choices = [
            'ا' => ['ا', 'آ'], 'آ' => ['ا', 'آ'],
            'گ' => ['گ', 'ک', 'ك'], 'ک' => ['ک', 'گ', 'ك'], 'ك' => ['ک', 'گ', 'ك'],
            'ی' => ['ی', 'ي', 'ى'], 'ي' => ['ی', 'ي', 'ى'], 'ى' => ['ی', 'ي', 'ى'],
        ];
        $variants = [''];

        foreach (preg_split('//u', $term, -1, PREG_SPLIT_NO_EMPTY) as $character) {
            $next = [];
            foreach ($variants as $variant) {
                foreach ($choices[$character] ?? [$character] as $choice) {
                    $next[] = $variant.$choice;
                }
            }
            $variants = array_slice(array_values(array_unique($next)), 0, 256);
        }

        return $variants;
    }

    private function whereAnyLike(Builder $query, array $columns, array $likes): Builder
    {
        return $query->where(function (Builder $query) use ($columns, $likes) {
            foreach ($columns as $column) {
                foreach ($likes as $like) {
                    $query->orWhere($column, 'like', $like);
                }
            }
        });
    }

    private function attributePayload($attributes): array
    {
        return collect($attributes)->map(fn (Attribute $attribute) => [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'slug' => $attribute->slug,
            'type' => $attribute->type->value,
            'unit' => $attribute->unit,
            'options' => $attribute->options ?? [],
            'is_required' => (bool) ($attribute->pivot->is_required ?? false),
        ])->values()->all();
    }
}
