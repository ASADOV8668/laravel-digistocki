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
use App\Support\PersianDigits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ListingImportController extends Controller
{
    public function create(Request $request)
    {
        $parser = $this->parser($request);
        $colorOptions = Attribute::query()->where('slug', 'color')->value('options') ?? [];

        return view('listings.import', compact('parser', 'colorOptions'));
    }

    public function preview(Request $request, ListingImportParserRegistry $registry): RedirectResponse|View
    {
        $parser = $this->parser($request);
        $validated = $request->validate(['text' => ['required', 'string', 'min:10', 'max:200000'], 'listing_date' => ['nullable', 'date']]);
        $result = $registry->make($parser)->parse($validated['text'], $validated['listing_date'] ?? null);
        $rows = $result['items'];
        if ($rows === []) {
            return back()->withErrors(['text' => 'از متن واردشده هیچ ردیف قابل تشخیصی پیدا نشد.'])->withInput();
        }
        $token = bin2hex(random_bytes(16));
        $request->session()->put('listing_import.preview.'.$token, ['parser_id' => $parser->id, 'rows' => $rows, 'text' => $validated['text'], 'date' => $result['detected_date'] ?? $validated['listing_date'] ?? null]);
        $colorOptions = Attribute::query()->where('slug', 'color')->value('options') ?? [];
        $rows = $this->withAttributeDefinitions($rows, $request->user());
        $request->session()->put('listing_import.preview.'.$token.'.rows', $rows);

        return view('listings.import-preview', [
            'parser' => $parser,
            'rows' => $rows,
            'token' => $token,
            'listingDate' => $result['detected_date'] ?? $validated['listing_date'] ?? null,
            'dateRequired' => (bool) $result['date_required'],
            'colorOptions' => $colorOptions,
        ]);
    }

    public function store(Request $request, ImportedListingService $service, ListingRules $rules, ImageService $imageService, ListingMediaService $mediaService): RedirectResponse
    {
        $token = (string) $request->input('token');
        $preview = $request->session()->get('listing_import.preview.'.$token);
        abort_unless(is_array($preview), 419);
        abort_unless($request->user()->listing_import_parser_id === ($preview['parser_id'] ?? null), 403);
        if (! $rules->canCreate($request->user())) {
            return redirect()->route('home')->with('status', 'ثبت آگهی برای این حساب در حال حاضر فعال نیست.');
        }

        $date = PersianDigits::toEnglish((string) $request->input('listing_date'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return back()->withErrors(['listing_date' => 'تاریخ آگهی را وارد کنید.'])->withInput();
        }
        $rows = $request->input('rows', []);
        $originalRows = $preview['rows'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return back()->withErrors(['rows' => 'حداقل یک ردیف برای ثبت انتخاب کنید.']);
        }

        $created = 0;
        $createdListings = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $submitted) {
                if (! is_array($submitted) || ! filter_var($submitted['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
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
                $attributeSlugs = array_column($source['attribute_definitions'] ?? [], 'slug', 'id');
                foreach ((array) ($submitted['attributes'] ?? []) as $attributeId => $attributeValue) {
                    $rulesForValue = match ($attributeSlugs[$attributeId] ?? null) {
                        'storage' => ['nullable', 'integer', 'between:1,9999'],
                        'battery_health' => ['nullable', 'integer', 'between:0,100'],
                        'color' => ['nullable', 'string', 'max:40'],
                        'registered' => ['nullable', 'boolean'],
                        default => null,
                    };
                    if ($rulesForValue !== null) {
                        $attributeValue = PersianDigits::toEnglish($attributeValue);
                        $key = 'rows.'.$index.'.attributes.'.$attributeId;
                        if (Validator::make(['value' => $attributeValue], ['value' => $rulesForValue])->fails()) {
                            throw ValidationException::withMessages([$key => 'مقدار واردشده برای این ویژگی معتبر نیست.']);
                        }
                        if ($attributeValue === '' || $attributeValue === null) {
                            unset($row['attributes'][(int) $attributeId]);

                            continue;
                        }
                    }
                    if ($attributeValue === '' || $attributeValue === [] || $attributeValue === null) {
                        continue;
                    }
                    $row['attributes'][(int) $attributeId] = $attributeValue;
                }
                $registrationId = array_search('registered', $attributeSlugs, true);
                $statusId = array_search('registration_status', $attributeSlugs, true);
                if ($registrationId !== false && $statusId !== false
                    && (string) ($row['attributes'][$registrationId] ?? '') !== (string) ($source['attributes'][$registrationId] ?? '')
                    && ($row['attributes'][$statusId] ?? null) === ($source['attributes'][$statusId] ?? null)) {
                    unset($row['attributes'][$statusId]);
                }
                if ($row['price'] === null) {
                    throw ValidationException::withMessages(['rows.'.$index.'.price' => 'قیمت این ردیف نامعتبر است.']);
                }
                $upload = $request->file('uploads.'.$index);
                $mediaId = filled($request->input('media_ids.'.$index)) ? (int) $request->input('media_ids.'.$index) : null;
                if (! $upload && ! $mediaId) {
                    throw ValidationException::withMessages(['uploads.'.$index => 'برای این ردیف یک تصویر از رسانه‌ها انتخاب یا تصویر جدیدی آپلود کنید.']);
                }
                $createdListings[] = $service->create($request->user(), $row, $upload, $mediaId, $date, $rules, $imageService, $mediaService);
                $created++;
            }
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
        $request->session()->forget('listing_import.preview.'.$token);
        collect($createdListings)->filter(fn ($listing): bool => $listing->status->value === 'pending')->each(
            fn ($listing) => User::query()->where('role', 'admin')->get()->each(fn (User $admin) => $admin->notify(new PendingListingNotification($listing))),
        );

        return redirect()->route('dashboard')->with('status', $created.' آگهی از متن واردشده ایجاد شد و برای بررسی ارسال گردید.');
    }

    private function parser(Request $request): ListingImportParser
    {
        $parser = $request->user()?->listingImportParser;
        abort_unless($parser?->is_active === true, 403);

        return $parser;
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

            $key = ($row['brand_id'] ?? 0).':'.$modelId;
            $primaryImage = $latestPrimaryImages->get($key)?->primaryImage;
            $row['media_id'] = $primaryImage?->media_id;
            $row['media_url'] = $primaryImage?->displayUrl(true);

            return $row;
        }, $rows);
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
