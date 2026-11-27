<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Listing;
use App\Models\ListingImportParser;
use App\Models\PhoneModel;
use App\Models\User;
use App\Notifications\PendingListingNotification;
use App\Services\ImageService;
use App\Services\ListingImport\ImportedListingService;
use App\Services\ListingImport\ListingImportParserRegistry;
use App\Services\ListingMediaService;
use App\Services\ListingRules;
use App\Support\PersianDate;
use App\Support\PersianDigits;
use App\Support\StorageCapacity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListingImportController extends Controller
{
    public function create(Request $request, ListingRules $rules): View
    {
        $seller = $this->seller($request);
        $parser = $seller?->listingImportParser;
        $colorOptions = Attribute::query()->where('slug', 'color')->value('options') ?? [];
        $adminImport = $request->routeIs('admin.listing-imports.test.*');
        $restriction = $adminImport && $seller ? $this->restriction($seller, $rules) : null;
        $token = $request->query('preview');
        if (is_string($token) && preg_match('/\A[a-f0-9]{32}\z/', $token)) {
            $preview = $request->session()->get('listing_import.preview.'.$token);
            abort_unless($parser && $this->matchesPreview($request, $preview, $seller, $parser), 419);
            $rows = $preview['rows'];
            foreach ($rows as $index => &$row) {
                $submitted = $request->old('rows.'.$index, []);
                if (! is_array($submitted)) {
                    continue;
                }
                $row['price'] = is_scalar($submitted['price'] ?? null) ? $submitted['price'] : $row['price'];
                if (is_array($submitted['attributes'] ?? null)) {
                    $row['attributes'] = array_replace($row['attributes'], $submitted['attributes']);
                }
            }
            unset($row);

            return view('listings.import-preview', [
                'parser' => $parser,
                'seller' => $seller,
                'rows' => $rows,
                'token' => $token,
                'listingDate' => $preview['date'],
                'colorOptions' => $colorOptions,
            ]);
        }

        $sellers = $adminImport ? User::query()->with('storefront')
            ->where('role', 'user')->whereHas('storefront', fn ($query) => $query->where('approval_status', 'approved'))
            ->orderBy('name')->get() : collect();

        return view('listings.import', compact('parser', 'colorOptions', 'seller', 'restriction', 'sellers'));
    }

    public function preview(Request $request, ListingImportParserRegistry $registry, ListingRules $rules): RedirectResponse|View
    {
        $seller = $this->seller($request);
        $parser = $seller?->listingImportParser;
        abort_unless($seller, 404);
        if ($request->routeIs('admin.listing-imports.test.*') && ($restriction = $this->restriction($seller, $rules))) {
            return $this->importPage($request, $seller)->withErrors(['seller' => $restriction])->withInput();
        }
        abort_unless($parser?->is_active, 403);
        $validated = $request->validate(['text' => ['required', 'string', 'min:10', 'max:200000'], 'listing_date' => ['nullable', 'string']]);
        $inputDate = $validated['listing_date'] ?? null;
        $listingDate = $inputDate === null ? today('Asia/Tehran')->toDateString() : $this->parseImportDate($inputDate);
        if ($listingDate === null) {
            return $this->importPage($request, $seller)
                ->withErrors(['listing_date' => 'تاریخ آگهی معتبر نیست. یک تاریخ شمسی با قالب سال/ماه/روز وارد کنید.'])
                ->withInput();
        }
        $result = $registry->make($parser)->parse($validated['text'], $listingDate);
        $rows = $result['items'];
        if ($rows === []) {
            return back()->withErrors(['text' => 'از متن واردشده هیچ ردیف قابل تشخیصی پیدا نشد.'])->withInput();
        }
        $token = bin2hex(random_bytes(16));
        $colorOptions = Attribute::query()->where('slug', 'color')->value('options') ?? [];
        $rows = $this->withAttributeDefinitions($rows, $seller);
        $request->session()->put('listing_import.preview.'.$token, [
            'actor_id' => $request->user()->id,
            'seller_id' => $seller->id,
            'admin' => $request->routeIs('admin.listing-imports.test.*'),
            'parser_id' => $parser->id,
            'rows' => $rows,
            'date' => $listingDate,
        ]);

        return view('listings.import-preview', [
            'parser' => $parser,
            'seller' => $seller,
            'rows' => $rows,
            'token' => $token,
            'listingDate' => $listingDate,
            'colorOptions' => $colorOptions,
        ]);
    }

    public function store(Request $request, ImportedListingService $service, ListingRules $rules, ImageService $imageService, ListingMediaService $mediaService): RedirectResponse
    {
        $seller = $this->seller($request);
        $parser = $seller?->listingImportParser;
        $adminImport = $request->routeIs('admin.listing-imports.test.*');
        $token = $request->input('token');
        abort_unless(is_string($token) && preg_match('/\A[a-f0-9]{32}\z/', $token), 419);
        $preview = $request->session()->get('listing_import.preview.'.$token);
        abort_unless($parser && $this->matchesPreview($request, $preview, $seller, $parser), 419);
        if ($adminImport && ($restriction = $this->restriction($seller, $rules))) {
            return $this->importPage($request, $seller)->withErrors(['seller' => $restriction]);
        }
        if (! $adminImport && ! $rules->canCreate($seller)) {
            return redirect()->route('home')->with('status', 'ثبت آگهی برای این حساب در حال حاضر فعال نیست.');
        }

        $date = $this->parseImportDate($request->string('listing_date')->toString());
        if ($date === null) {
            return $this->importPage($request, $seller, ['preview' => $token])
                ->withErrors(['listing_date' => 'تاریخ آگهی معتبر نیست. یک تاریخ شمسی با قالب سال/ماه/روز وارد کنید.'])
                ->withInput();
        }
        $rows = $request->input('rows', []);
        $originalRows = $preview['rows'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return $this->importPage($request, $seller, ['preview' => $token])
                ->withErrors(['rows' => 'حداقل یک ردیف برای ثبت انتخاب کنید.']);
        }

        $created = 0;
        $createdListings = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $submitted) {
                if (! is_array($submitted) || ! filter_var($submitted['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }
                if (($submitted['action'] ?? 'new') === 'skip') {
                    continue;
                }
                $source = $originalRows[$index] ?? null;
                if (! is_array($source) || ! ($source['supported'] ?? false)) {
                    continue;
                }
                $row = $source;
                $row['price'] = $this->normalizePrice($submitted['price'] ?? $source['price'] ?? null, (int) ($source['price'] ?? 0));
                $row['description'] = trim((string) ($submitted['description'] ?? $source['description'] ?? ''));
                $row['attributes'] = (array) ($source['attributes'] ?? []);
                $submittedAttributes = (array) ($submitted['attributes'] ?? []);
                $storage = collect($source['attribute_definitions'] ?? [])->firstWhere('slug', 'storage');
                if ($storage) {
                    $id = $storage['id'];
                    $value = array_key_exists($id, $submittedAttributes) ? $submittedAttributes[$id] : ($row['attributes'][$id] ?? null);
                    unset($submittedAttributes[$id], $row['attributes'][$id]);
                    if ($value !== null && $value !== '') {
                        $capacity = StorageCapacity::forAttribute($value, $storage);
                        if ($capacity === null) {
                            throw ValidationException::withMessages([
                                'rows.'.$index.'.attributes.'.$id => 'حافظه ردیف '.($index + 1).' معتبر نیست. گزینه حافظه مدل یا مقداری مانند 512GB یا 1TB را وارد کنید.',
                            ]);
                        }
                        $row['attributes'][$id] = $capacity;
                    }
                }
                foreach ($submittedAttributes as $attributeId => $attributeValue) {
                    if ($attributeValue === '' || $attributeValue === [] || $attributeValue === null) {
                        continue;
                    }
                    $row['attributes'][(int) $attributeId] = $attributeValue;
                }
                if ($row['price'] === null) {
                    throw ValidationException::withMessages(['rows.'.$index.'.price' => 'قیمت این ردیف نامعتبر است.']);
                }
                $upload = $request->file('uploads.'.$index);
                $mediaId = filled($request->input('media_ids.'.$index)) ? (int) $request->input('media_ids.'.$index) : null;
                if (! $upload && ! $mediaId) {
                    throw ValidationException::withMessages(['uploads.'.$index => 'برای این ردیف یک تصویر از رسانه‌ها انتخاب یا تصویر جدیدی آپلود کنید.']);
                }
                $createdListings[] = $service->create($seller, $row, $upload, $mediaId, $date, $rules, $imageService, $mediaService, requireReview: $adminImport);
                $created++;
            }
            if ($adminImport && $created === 0) {
                throw ValidationException::withMessages(['rows' => 'حداقل یک ردیف قابل ثبت انتخاب کنید.']);
            }
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            if ($exception instanceof ValidationException && ! $request->expectsJson()) {
                return $this->importPage($request, $seller, ['preview' => $token])->withErrors($exception->errors())->withInput();
            }
            throw $exception;
        }
        $request->session()->forget('listing_import.preview.'.$token);
        collect($createdListings)->filter(fn ($listing): bool => $listing->status->value === 'pending')->each(
            fn ($listing) => User::query()->where('role', 'admin')->get()->each(fn (User $admin) => $admin->notify(new PendingListingNotification($listing))),
        );

        return redirect()->route($adminImport ? 'admin.listings.index' : 'dashboard')
            ->with('status', $created.' آگهی از متن واردشده ایجاد شد و برای بررسی ارسال گردید.');
    }

    private function seller(Request $request): ?User
    {
        if (! $request->routeIs('admin.listing-imports.test.*')) {
            $user = $request->user();
            abort_unless($user?->canImportListings(), 403);

            return $user;
        }

        if (! $request->query->has('seller')) {
            return null;
        }

        $seller = User::query()->with(['storefront', 'listingImportParser'])->findOrFail((int) $request->query('seller'));
        abort_unless($seller->isUser() && $seller->isSeller(), 404);

        return $seller;
    }

    private function restriction(User $seller, ListingRules $rules): ?string
    {
        if (! $seller->canImportListings()) {
            return 'برای این فروشنده قالب فعال ایمپورت آگهی تعیین نشده است.';
        }

        if ($rules->canCreate($seller)) {
            return null;
        }

        return $rules->activeCount($seller) >= ListingRules::MAX_ACTIVE_LISTINGS
            ? 'این فروشنده به سقف '.ListingRules::MAX_ACTIVE_LISTINGS.' آگهی فعال رسیده است.'
            : 'ثبت آگهی برای این فروشنده در حال حاضر فعال نیست.';
    }

    private function matchesPreview(Request $request, mixed $preview, User $seller, ListingImportParser $parser): bool
    {
        return is_array($preview)
            && ($preview['actor_id'] ?? null) === $request->user()->id
            && $preview['seller_id'] === $seller->id
            && $preview['admin'] === $request->routeIs('admin.listing-imports.test.*')
            && $preview['parser_id'] === $parser->id;
    }

    private function importPage(Request $request, User $seller, array $parameters = []): RedirectResponse
    {
        if ($request->routeIs('admin.listing-imports.test.*')) {
            return redirect()->route('admin.listing-imports.test.create', ['seller' => $seller->id] + $parameters);
        }

        return redirect()->route('listings.import.create', $parameters);
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function withAttributeDefinitions(array $rows, User $user): array
    {
        $modelIds = collect($rows)->pluck('phone_model_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values();
        if ($modelIds->isEmpty()) {
            return $rows;
        }

        $models = PhoneModel::query()
            ->whereIn('id', $modelIds)
            ->with(['attributes' => fn ($query) => $query
                ->where('attributes.is_active', true)
                ->with('optionRestrictions')
                ->orderBy('model_attributes.sort_order')])
            ->get()
            ->keyBy('id');

        $latestPrimaryImages = Listing::query()
            ->where('user_id', $user->id)
            ->whereIn('brand_id', collect($rows)->pluck('brand_id')->filter()->unique())
            ->whereIn('phone_model_id', $modelIds)
            ->with(['primaryImage.media'])
            ->latest('created_at')
            ->get()
            ->filter(fn (Listing $listing): bool => $listing->primaryImage?->media !== null)
            ->unique(fn (Listing $listing): string => $listing->brand_id.':'.$listing->phone_model_id)
            ->keyBy(fn (Listing $listing): string => $listing->brand_id.':'.$listing->phone_model_id);

        return array_map(function (array $row) use ($models, $latestPrimaryImages): array {
            $modelId = (int) ($row['phone_model_id'] ?? 0);
            $model = $models->get($modelId);

            $row['attribute_definitions'] = $model?->attributes
                ->filter(fn (Attribute $attribute): bool => $attribute->isVisibleForModel($modelId))
                ->map(fn (Attribute $attribute): array => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'slug' => $attribute->slug,
                    'type' => $attribute->type->value,
                    'unit' => $attribute->unit,
                    'options' => $attribute->optionsForModel($modelId),
                ])
                ->values()
                ->all() ?? [];

            $storage = collect($row['attribute_definitions'])->firstWhere('slug', 'storage');
            if ($storage && isset($row['attributes'][$storage['id']])) {
                $row['attributes'][$storage['id']] = StorageCapacity::forAttribute($row['attributes'][$storage['id']], $storage);
            }

            $key = ($row['brand_id'] ?? 0).':'.$modelId;
            $primaryImage = $latestPrimaryImages->get($key)?->primaryImage;
            $row['media_id'] = $primaryImage?->media_id;
            $row['media_url'] = $primaryImage?->displayUrl(true);

            return $row;
        }, $rows);
    }

    private function parseImportDate(string $value): ?string
    {
        $submittedDate = PersianDigits::toEnglish(trim($value));
        $date = null;
        if (preg_match('~^\d{4}/\d{1,2}/\d{1,2}$~D', $submittedDate)) {
            $canonicalDate = PersianDate::fromParts(...explode('/', $submittedDate));
            $parsedDate = PersianDate::parseDate($canonicalDate);
            if ($parsedDate && PersianDigits::toEnglish(PersianDate::format($parsedDate)) === $canonicalDate) {
                $date = $parsedDate->toDateString();
            }
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $submittedDate, $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            $date = $submittedDate;
        }

        return $date;
    }

    private function normalizePrice(mixed $value, ?int $sourcePrice = null): ?int
    {
        $value = PersianDigits::toEnglish($value);
        if (! is_scalar($value)) {
            return null;
        }
        $raw = trim((string) $value);
        if ($sourcePrice && preg_replace('/\D/', '', $raw) === (string) $sourcePrice) {
            return $sourcePrice;
        }
        $hasThousandsSeparator = preg_match('/[,\/٬،]/u', $raw) === 1 || preg_match('/\.\d{3}(?:\D|$)/', $raw) === 1;
        $normalized = preg_replace('/[^\d]/', '', $raw) ?? '';
        if ($normalized === '') {
            return null;
        }
        $number = (int) $normalized;

        return $hasThousandsSeparator || $number < 1000000 ? $number * 1000 : $number;
    }
}
