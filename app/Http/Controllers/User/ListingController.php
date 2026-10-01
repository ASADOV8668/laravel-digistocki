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
use App\Services\ContactOtpService;
use App\Services\ImageService;
use App\Services\ListingRules;
use App\Services\SystemOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
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
        $brands = Brand::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $this->whereAnyLike($query, ['name', 'name_en'], $likes))
            ->orderBy('name')
            ->limit(6)
            ->get();

        $models = PhoneModel::query()
            ->where('is_active', true)
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

    public function modelAttributes(Request $request, PhoneModel $phoneModel)
    {
        abort_unless($phoneModel->is_active, 404);

        $attributes = $phoneModel->attributes()
            ->where('attributes.is_active', true)
            ->when(! $request->boolean('all'), fn ($query) => $query->where('attributes.is_filterable', true))
            ->orderBy('attributes.sort_order')
            ->get();

        return response()->json($attributes->map(fn (Attribute $attribute) => [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'slug' => $attribute->slug,
            'type' => $attribute->type->value,
            'unit' => $attribute->unit,
            'options' => $attribute->options ?? [],
            'is_filterable' => $attribute->is_filterable,
            'is_required' => (bool) $attribute->pivot->is_required,
            'sort_order' => $attribute->sort_order,
        ])->values());
    }

    public function models(Request $request)
    {
        $likes = $this->searchLikes((string) $request->input('q'));

        $models = PhoneModel::query()
            ->where('is_active', true)
            ->with('brand')
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

    public function index(Request $request)
    {
        $selectedModel = $request->integer('phone_model_id')
            ? PhoneModel::query()->where('is_active', true)->with(['attributes' => fn ($query) => $query->where('is_active', true)->where('is_filterable', true)->orderBy('sort_order')])->find($request->integer('phone_model_id'))
            : null;
        $filterAttributes = $selectedModel?->attributes ?? collect();
        $filterAttributesById = $filterAttributes->keyBy('id');
        $query = Listing::query()->published()->with(['brand', 'phoneModel', 'province', 'city', 'primaryImage', 'images', 'attributeValues.attribute', 'user.storefront'])->withViewerFavorite(auth()->id());

        $query->when($request->integer('brand_id'), fn (Builder $query, int $brandId) => $query->where('brand_id', $brandId));
        $query->when($request->integer('phone_model_id'), fn (Builder $query, int $modelId) => $query->where('phone_model_id', $modelId));
        $query->when($request->integer('province_id'), fn (Builder $query, int $provinceId) => $query->where('province_id', $provinceId));
        $query->when($request->integer('city_id'), fn (Builder $query, int $cityId) => $query->where('city_id', $cityId));
        $query->when($request->integer('min_price'), fn (Builder $query, int $price) => $query->where('price', '>=', $price));
        $query->when($request->integer('max_price'), fn (Builder $query, int $price) => $query->where('price', '<=', $price));
        $query->when($request->filled('q'), function (Builder $query) use ($request) {
            $rawTerm = trim((string) $request->input('q'));
            $likes = $this->searchLikes($rawTerm);
            $query->where(function (Builder $query) use ($likes) {
                $query->where(function (Builder $title) use ($likes) {
                    foreach ($likes as $like) {
                        $title->orWhere('title', 'like', $like);
                    }
                })->orWhereHas('brand', fn (Builder $brand) => $this->whereAnyLike($brand, ['name', 'name_en'], $likes))
                    ->orWhereHas('phoneModel', fn (Builder $model) => $this->whereAnyLike($model, ['name', 'name_fa', 'name_en'], $likes));
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
                    default => $query->where('value_string', $value),
                };
            });
        }

        $sort = in_array($request->input('sort'), ['newest', 'price_asc', 'price_desc', 'views'], true)
            ? $request->input('sort')
            : 'newest';

        match ($sort) {
            'price_asc' => $query->orderByRaw('price IS NULL')->orderBy('price')->latest('published_at'),
            'price_desc' => $query->orderByRaw('price IS NULL')->orderByDesc('price')->latest('published_at'),
            'views' => $query->orderByDesc('views_count')->latest('published_at'),
            default => $query->latest('published_at'),
        };

        $selectedBrand = $request->integer('brand_id') ? Brand::query()->where('is_active', true)->find($request->integer('brand_id')) : null;
        $selectedProvince = $request->integer('province_id') ? Province::query()->find($request->integer('province_id')) : null;
        $selectedCity = $request->integer('city_id') ? City::query()->find($request->integer('city_id')) : null;
        $without = fn (array $keys): string => route('listings.index', Arr::except($request->query(), $keys));
        $activeFilters = [];

        if ($request->filled('q')) {
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

        $filterAttributesPayload = $filterAttributes->map(fn (Attribute $attribute) => [
            'id' => $attribute->id,
            'name' => $attribute->name,
            'slug' => $attribute->slug,
            'type' => $attribute->type->value,
            'unit' => $attribute->unit,
            'options' => $attribute->options ?? [],
        ])->values()->all();

        $listings = $query->paginate(12)->withQueryString();
        $priceCeiling = (int) Listing::query()->published()->whereNotNull('price')->max('price');
        $priceCeiling = max(1000000, (int) (ceil($priceCeiling / 1000000) * 1000000));
        $priceCeiling = max($priceCeiling, (int) $request->integer('max_price'));

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('listings.partials.results', compact('listings', 'sort', 'activeFilters'))->render(),
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
            'priceCeiling' => $priceCeiling,
        ]);
    }

    public function show(Listing $listing)
    {
        $this->authorize('view', $listing);
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
        $contactRevealed = in_array($listing->id, $revealedContactIds, true);
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

    public function create(ListingRules $rules, SystemOptions $options)
    {
        if (! $rules->canCreate(auth()->user())) {
            return redirect()->route('home')->with('status', 'در حال حاضر امکان ثبت آگهی برای حساب شما فعال نیست.');
        }
        $selectedModel = old('phone_model_id')
            ? PhoneModel::query()->where('is_active', true)->with(['attributes' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])->find(old('phone_model_id'))
            : null;

        return view('listings.create', [
            'brands' => Brand::query()->where('is_active', true)->with(['phoneModels' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'attributes' => Attribute::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'provinces' => Province::query()->orderBy('name')->get(),
            'initialModelAttributes' => $this->attributePayload($selectedModel?->attributes ?? collect()),
            'maxImageUploadMb' => $options->maxImageUploadMb(),
            'maxImageUploadCount' => $options->maxImageUploadCount(),
            'allowContactPrice' => $options->allowContactPrice(),
        ]);
    }

    public function edit(Listing $listing, SystemOptions $options)
    {
        $this->authorize('update', $listing);
        $listing->load(['images', 'attributeValues', 'phoneModel']);
        $attributes = $listing->phoneModel->attributes()
            ->where('attributes.is_active', true)
            ->orderBy('attributes.sort_order')
            ->get();

        return view('listings.edit', [
            'listing' => $listing,
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
                'phone_model_id' => 'برای این مدل در '.\App\Support\PersianNumber::digits($options->listingDuplicateCooldownHours()).' ساعت گذشته آگهی ثبت کرده‌اید.',
            ]);
        }

        $storedFiles = [];
        try {
            $listing = DB::transaction(function () use ($validated, $request, $model, $imageService, &$storedFiles) {
                $listingData = $validated;
                unset($listingData['attributes'], $listingData['images']);
                $listing = Listing::create([
                    ...$listingData,
                    'user_id' => $request->user()->id,
                    'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(8)),
                    'status' => 'pending',
                    'expires_at' => null,
                ]);

                $this->saveAttributeValues($listing, $model, (array) ($validated['attributes'] ?? []));
                $this->storeImages($listing, (array) ($validated['images'] ?? []), $imageService, $storedFiles);

                return $listing;
            });
        } catch (Throwable $exception) {
            $imageService->deleteMany($storedFiles);

            throw $exception;
        }

        return redirect()->route('listings.show', $listing)->with('status', 'آگهی شما برای بررسی ارسال شد.');
    }

    public function update(UpdateListingRequest $request, Listing $listing, ListingRules $rules, ImageService $imageService, SystemOptions $options)
    {
        $validated = $request->validated();
        $brand = Brand::query()->whereKey($validated['brand_id'])->where('is_active', true)->firstOrFail();
        $model = PhoneModel::query()->whereKey($validated['phone_model_id'])->where('brand_id', $brand->id)->where('is_active', true)->firstOrFail();

        if ($rules->hasRecentDuplicate($request->user(), $brand->id, $model->id, null, $listing->id)) {
            throw ValidationException::withMessages([
                'phone_model_id' => 'برای این مدل در '.\App\Support\PersianNumber::digits($options->listingDuplicateCooldownHours()).' ساعت گذشته آگهی دیگری ثبت کرده‌اید.',
            ]);
        }

        $storedFiles = [];
        try {
            DB::transaction(function () use ($validated, $listing, $model, $imageService, &$storedFiles) {
                $listingData = $validated;
                unset($listingData['attributes'], $listingData['images']);
                $listing->update([...$listingData, 'status' => 'pending', 'rejection_reason' => null, 'published_at' => null]);
                $listing->attributeValues()->delete();
                $this->saveAttributeValues($listing, $model, (array) ($validated['attributes'] ?? []));
                $this->storeImages($listing, (array) ($validated['images'] ?? []), $imageService, $storedFiles);
            });
        } catch (Throwable $exception) {
            $imageService->deleteMany($storedFiles);

            throw $exception;
        }

        return redirect()->route('listings.show', $listing)->with('status', 'تغییرات ذخیره و آگهی دوباره برای بررسی ارسال شد.');
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

    public function renew(Request $request, Listing $listing, ListingRules $rules)
    {
        $this->authorize('renew', $listing);

        if (! $rules->canRenew($request->user())) {
            return back()->withErrors(['listing' => 'در حال حاضر امکان ارسال مجدد این آگهی وجود ندارد.']);
        }

        $listing->forceFill([
            'status' => 'pending',
            'published_at' => null,
            'expires_at' => null,
            'rejection_reason' => null,
        ])->save();

        return back()->with('status', 'آگهی برای بررسی مجدد ارسال شد.');
    }

    public function destroyImage(Request $request, Listing $listing, ListingImage $image, ImageService $imageService)
    {
        $this->authorize('update', $listing);
        abort_unless($image->listing_id === $listing->id, 404);
        $imageService->delete($image->path, $image->thumbnail_path);
        $image->delete();

        if (! $listing->images()->where('is_primary', true)->exists() && ($replacement = $listing->images()->first())) {
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

            app(\App\Services\ListingAttributeOptionRegistrar::class)->remember($attribute, $value);

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

    private function storeImages(Listing $listing, array $images, ImageService $imageService, array &$storedFiles = []): void
    {
        $uploads = array_values(array_filter($images, fn ($image) => $image instanceof UploadedFile));
        if ($uploads === []) {
            return;
        }

        $hasPrimary = $listing->images()->where('is_primary', true)->exists();
        $nextSortOrder = (int) $listing->images()->max('sort_order') + 1;

        foreach ($uploads as $image) {
            $stored = $imageService->store($image, 'listings/'.$listing->id);
            $storedFiles[] = [$stored['path'] ?? null, $stored['thumbnail_path'] ?? null];
            $listing->images()->create([
                ...$stored,
                'is_primary' => ! $hasPrimary,
                'sort_order' => $nextSortOrder++,
            ]);
            $hasPrimary = true;
        }
    }

    private function normalizeSearchTerm(string $term): string
    {
        return trim(strtr($term, [
            'آ' => 'ا', 'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ۀ' => 'ه', 'ة' => 'ه',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]));
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
