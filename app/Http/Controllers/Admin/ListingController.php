<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttributeType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreListingRequest;
use App\Http\Requests\Admin\UpdateListingRequest;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\ListingImage;
use App\Models\PhoneModel;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use App\Services\ImageService;
use App\Services\ListingRules;
use App\Services\SystemOptions;
use App\Support\MobileNumber;
use App\Support\PersianDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sadegh19b\LaravelIranCities\Models\Province;
use Throwable;

class ListingController extends Controller
{
    public function create(SystemOptions $options)
    {
        return view('admin.listings.form', $this->formData($options));
    }

    public function edit(Listing $listing, SystemOptions $options)
    {
        $listing->load(['images', 'attributeValues', 'phoneModel', 'user']);
        $attributes = $listing->phoneModel->attributes()->where('attributes.is_active', true)->orderBy('attributes.sort_order')->get();

        return view('admin.listings.form', $this->formData($options, $listing, $attributes));
    }

    public function store(StoreListingRequest $request, ImageService $imageService)
    {
        $validated = $request->validated();
        $brand = Brand::query()->whereKey($validated['brand_id'])->firstOrFail();
        $model = PhoneModel::query()->whereKey($validated['phone_model_id'])->where('brand_id', $brand->id)->firstOrFail();
        $storedFiles = [];

        try {
            $listing = DB::transaction(function () use ($validated, $model, $imageService, &$storedFiles) {
                $data = $validated;
                unset($data['attributes'], $data['images'], $data['user_id']);
                $listing = Listing::create([...$data, 'user_id' => $validated['user_id'], 'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(8)), 'status' => 'pending', 'expires_at' => null]);
                $this->saveAttributeValues($listing, $model, (array) ($validated['attributes'] ?? []));
                $this->storeImages($listing, (array) ($validated['images'] ?? []), $imageService, $storedFiles);

                return $listing;
            });
        } catch (Throwable $exception) {
            $imageService->deleteMany($storedFiles);
            throw $exception;
        }

        return redirect()->route('admin.listings.index')->with('status', 'آگهی جدید با موفقیت ثبت شد و در انتظار بررسی قرار گرفت.');
    }

    public function update(UpdateListingRequest $request, Listing $listing, ImageService $imageService)
    {
        $validated = $request->validated();
        $brand = Brand::query()->whereKey($validated['brand_id'])->firstOrFail();
        $model = PhoneModel::query()->whereKey($validated['phone_model_id'])->where('brand_id', $brand->id)->firstOrFail();
        $storedFiles = [];

        try {
            DB::transaction(function () use ($validated, $listing, $model, $imageService, &$storedFiles) {
                $data = $validated;
                unset($data['attributes'], $data['images'], $data['user_id'], $data['status']);
                $status = $validated['status'];
                $listing->update([...$data, 'user_id' => $validated['user_id'], 'status' => $status, 'published_at' => $status === 'approved' ? now() : $listing->published_at, 'expires_at' => $status === 'approved' ? app(ListingRules::class)->expiryDate() : ($status === 'expired' ? now()->subSecond() : $listing->expires_at), 'rejection_reason' => $status === 'rejected' ? ($listing->rejection_reason ?: 'رد توسط مدیریت') : null]);
                $listing->attributeValues()->delete();
                $this->saveAttributeValues($listing, $model, (array) ($validated['attributes'] ?? []));
                $this->storeImages($listing, (array) ($validated['images'] ?? []), $imageService, $storedFiles);
            });
        } catch (Throwable $exception) {
            $imageService->deleteMany($storedFiles);
            throw $exception;
        }

        return redirect()->route('admin.listings.index')->with('status', 'تغییرات آگهی ذخیره و برای بررسی ارسال شد.');
    }

    public function deleteImage(Listing $listing, ListingImage $image, ImageService $imageService)
    {
        abort_unless($image->listing_id === $listing->id, 404);
        $wasPrimary = (bool) $image->is_primary;
        $imageService->delete($image->path, $image->thumbnail_path);
        $image->delete();
        if ($wasPrimary && ($replacement = $listing->images()->first())) {
            $replacement->forceFill(['is_primary' => true])->save();
        }

        return back()->with('status', 'تصویر آگهی حذف شد.');
    }

    public function userSearch(Request $request)
    {
        $term = trim((string) $request->input('q'));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $normalized = MobileNumber::normalize($term) ?? $term;
        $likes = array_values(array_unique(['%'.$term.'%', '%'.$normalized.'%']));

        return response()->json(User::query()->where(function ($query) use ($likes) {
            foreach ($likes as $like) {
                $query->orWhere('name', 'like', $like)->orWhere('mobile', 'like', $like)->orWhere('email', 'like', $like);
            }
        })->orderBy('name')->limit(15)->get(['id', 'name', 'mobile', 'email'])->map(fn (User $user) => ['id' => $user->id, 'label' => $user->name, 'mobile' => $user->mobile, 'email' => $user->email])->values());
    }

    public function index(Request $request)
    {
        $status = $request->input('status');
        $statuses = ['pending', 'approved', 'rejected', 'sold', 'expired'];
        $dateFrom = PersianDate::parseDate($request->input('date_from'));
        $dateTo = PersianDate::parseDate($request->input('date_to'));
        $statusCounts = Listing::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $listings = Listing::with(['user', 'brand', 'phoneModel', 'primaryImage'])
            ->when(in_array($status, $statuses, true), fn (Builder $query) => $query->where('status', $status))
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function (Builder $query) use ($term) {
                    $query->where('title', 'like', $term)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('mobile', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('brand', fn (Builder $brand) => $brand->where('name', 'like', $term)->orWhere('name_en', 'like', $term))
                        ->orWhereHas('phoneModel', fn (Builder $model) => $model->where('name', 'like', $term)->orWhere('name_fa', 'like', $term)->orWhere('name_en', 'like', $term));
                });
            })
            ->when($dateFrom, fn (Builder $query) => $query->where('created_at', '>=', $dateFrom->startOfDay()))
            ->when($dateTo, fn (Builder $query) => $query->where('created_at', '<=', $dateTo->endOfDay()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.listings.index', compact('listings', 'statuses', 'status', 'statusCounts'));
    }

    public function approve(Listing $listing, ListingRules $rules)
    {
        $this->authorize('approve', $listing);
        abort_unless($rules->canPublish($listing), 422, 'این آگهی در وضعیت فعلی قابل تأیید نیست.');
        $listing = $rules->publish($listing);
        $listing->user->notify(new ListingStatusNotification($listing, 'approved'));

        return back()->with('status', 'آگهی تأیید شد.');
    }

    public function reject(Request $request, Listing $listing, ListingRules $rules)
    {
        $this->authorize('reject', $listing);
        abort_unless($rules->canReject($listing), 422, 'این آگهی در وضعیت فعلی قابل رد نیست.');
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $listing = $rules->reject($listing, $validated['rejection_reason']);
        $listing->user->notify(new ListingStatusNotification($listing, 'rejected', $validated['rejection_reason']));

        return back()->with('status', 'آگهی رد شد.');
    }

    private function formData(SystemOptions $options, ?Listing $listing = null, $attributes = null): array
    {
        $selectedModel = $listing?->phoneModel ?? (old('phone_model_id') ? PhoneModel::query()->find(old('phone_model_id')) : null);
        $attributes ??= $selectedModel?->attributes()->where('attributes.is_active', true)->orderBy('attributes.sort_order')->get() ?? collect();

        return [
            'listing' => $listing,
            'users' => $listing?->user ? collect([$listing->user]) : collect(),
            'brands' => Brand::query()->where('is_active', true)->with(['phoneModels' => fn ($query) => $query->where('is_active', true)->orderBy('name')])->orderBy('name')->get(),
            'provinces' => Province::query()->orderBy('name')->get(),
            'attributes' => $attributes,
            'initialValues' => $listing ? $listing->attributeValues->mapWithKeys(fn ($value) => [$value->attribute_id => $value->value_json ?? $value->value_string ?? $value->value_integer ?? $value->value_decimal ?? $value->value_boolean])->all() : [],
            'initialModelAttributes' => $attributes->map(fn (Attribute $attribute) => ['id' => $attribute->id, 'name' => $attribute->name, 'slug' => $attribute->slug, 'type' => $attribute->type->value, 'unit' => $attribute->unit, 'options' => $attribute->options ?? [], 'is_required' => (bool) ($attribute->pivot->is_required ?? false)])->values()->all(),
            'maxImageUploadMb' => $options->maxImageUploadMb(),
            'maxImageUploadCount' => $options->maxImageUploadCount(),
            'allowContactPrice' => $options->allowContactPrice(),
        ];
    }

    private function saveAttributeValues(Listing $listing, PhoneModel $model, array $values): void
    {
        $attributes = $model->attributes()->where('attributes.is_active', true)->get()->keyBy('id');
        $now = now();
        $payloads = [];
        foreach ($values as $attributeId => $value) {
            $attribute = $attributes->get((int) $attributeId);
            if (! $attribute || $value === null || $value === '' || (is_array($value) && $value === [])) {
                continue;
            }

            app(\App\Services\ListingAttributeOptionRegistrar::class)->remember($attribute, $value);

            $payload = ['listing_id' => $listing->id, 'attribute_id' => $attribute->id, 'value_string' => null, 'value_integer' => null, 'value_decimal' => null, 'value_boolean' => null, 'value_json' => null, 'created_at' => $now, 'updated_at' => $now];
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

    private function storeImages(Listing $listing, array $images, ImageService $imageService, array &$storedFiles): void
    {
        $uploads = array_values(array_filter($images, fn ($image) => $image instanceof UploadedFile));
        $hasPrimary = $listing->images()->where('is_primary', true)->exists();
        $sortOrder = (int) $listing->images()->max('sort_order') + 1;
        foreach ($uploads as $image) {
            $stored = $imageService->store($image, 'listings/'.$listing->id);
            $storedFiles[] = [$stored['path'] ?? null, $stored['thumbnail_path'] ?? null];
            $listing->images()->create([...$stored, 'is_primary' => ! $hasPrimary, 'sort_order' => $sortOrder++]);
            $hasPrimary = true;
        }
    }
}
