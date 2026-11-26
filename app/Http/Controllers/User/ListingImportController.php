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
use App\Services\ListingImport\CatalogColorMatcher;
use App\Services\ListingImport\ImportedListingService;
use App\Services\ListingImport\InventoryMatch;
use App\Services\ListingImport\ListingImportParserRegistry;
use App\Services\ListingMediaService;
use App\Services\ListingRules;
use App\Services\SystemOptions;
use App\Support\PersianDigits;
use App\Support\RegistrationStatus;
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
        $listingDate = today('Asia/Tehran')->toDateString();
        $token = $request->query('preview');
        if (is_string($token) && preg_match('/\A[a-f0-9]{32}\z/', $token)) {
            $preview = $request->session()->get('listing_import.preview.'.$token);
            abort_unless(is_array($preview) && $parser->id === $preview['parser_id'], 419);
            $rows = $preview['rows'];
            foreach ($rows as $index => &$row) {
                $oldRow = $request->old('rows.'.$index, []);
                if (is_array($oldRow)) {
                    $row['price'] = is_scalar($oldRow['price'] ?? null) ? $oldRow['price'] : $row['price'];
                    if (is_array($oldRow['attributes'] ?? null)) {
                        $row['attributes'] = array_replace($row['attributes'], $oldRow['attributes']);
                    }
                }
            }

            return view('listings.import-preview', [
                'parser' => $parser,
                'rows' => $rows,
                'token' => $token,
                'listingDate' => $preview['date'],
                'colorOptions' => $colorOptions,
                'unmatchedListings' => $preview['unmatchedListings'] ?? [],
            ]);
        }

        return view('listings.import', compact('parser', 'colorOptions', 'listingDate'));
    }

    public function preview(Request $request, ListingImportParserRegistry $registry, InventoryMatch $inventory, CatalogColorMatcher $colors): RedirectResponse|View
    {
        $parser = $this->parser($request);
        $validated = $request->validate(['text' => ['required', 'string', 'min:10', 'max:200000'], 'listing_date' => ['nullable', 'date_format:Y-m-d']]);
        $listingDate = $validated['listing_date'] ?? today('Asia/Tehran')->toDateString();
        $result = $registry->make($parser)->parse($validated['text'], $listingDate);
        $rows = $result['items'];
        if ($rows === []) {
            return back()->withErrors(['text' => 'از متن واردشده هیچ ردیف قابل تشخیصی پیدا نشد.'])->withInput();
        }
        $token = bin2hex(random_bytes(16));
        $colorOptions = Attribute::query()->where('slug', 'color')->value('options') ?? [];
        $rows = $this->withAttributeDefinitions($rows, $request->user(), $colors);
        [$rows, $unmatchedListings] = $inventory->preview($request->user(), $rows);
        $request->session()->put('listing_import.preview.'.$token, [
            'parser_id' => $parser->id, 'rows' => $rows, 'date' => $listingDate, 'unmatchedListings' => $unmatchedListings,
        ]);

        return view('listings.import-preview', [
            'parser' => $parser,
            'rows' => $rows,
            'token' => $token,
            'listingDate' => $listingDate,
            'colorOptions' => $colorOptions,
            'unmatchedListings' => $unmatchedListings,
        ]);
    }

    public function store(Request $request, ImportedListingService $service, ListingRules $rules, ImageService $imageService, ListingMediaService $mediaService, InventoryMatch $inventory): RedirectResponse
    {
        $parser = $this->parser($request);
        $token = (string) $request->input('token');
        $preview = $request->session()->get('listing_import.preview.'.$token);
        abort_unless(is_array($preview), 419);
        abort_unless($parser->id === ($preview['parser_id'] ?? null), 403);
        if (! app(SystemOptions::class)->listingsEnabled()) {
            return redirect()->route('home')->with('status', 'ثبت آگهی برای این حساب در حال حاضر فعال نیست.');
        }

        $date = PersianDigits::toEnglish((string) $request->input('listing_date'));
        if (Validator::make(['listing_date' => $date], ['listing_date' => ['required', 'date_format:Y-m-d']])->fails()) {
            return redirect()->route('listings.import.create', ['preview' => $token])->withErrors(['listing_date' => 'تاریخ معتبر آگهی را وارد کنید.'])->withInput();
        }
        $rows = $request->input('rows', []);
        $originalRows = $preview['rows'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return redirect()->route('listings.import.create', ['preview' => $token])->withErrors(['rows' => 'حداقل یک ردیف برای ثبت انتخاب کنید.']);
        }

        $updated = 0;
        $createdListings = [];
        DB::beginTransaction();
        try {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            foreach ($rows as $index => $submitted) {
                if (! is_array($submitted) || ! filter_var($submitted['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }
                $source = $originalRows[$index] ?? null;
                if (! is_array($source) || ! ($source['supported'] ?? false)) {
                    continue;
                }
                $action = $submitted['action'] ?? (($source['inventory_state'] ?? 'new') === 'new' ? 'new' : 'skip');
                if ($action === 'skip') {
                    continue;
                }
                if (! in_array($action, ['new', 'update'], true)) {
                    throw ValidationException::withMessages(['rows.'.$index.'.action' => 'عملیات انتخاب‌شده معتبر نیست.']);
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
                if ($registrationId !== false && $statusId !== false) {
                    $selectedStatus = $submitted['attributes'][$statusId] ?? null;
                    if (is_string($selectedStatus) && in_array($selectedStatus, [
                        RegistrationStatus::REGISTERED, RegistrationStatus::OWNER, RegistrationStatus::OTHER, RegistrationStatus::UNREGISTERED, RegistrationStatus::UNKNOWN,
                    ], true)) {
                        $registered = RegistrationStatus::registered($selectedStatus);
                        unset($row['attributes'][$registrationId]);
                        if ($registered !== null) {
                            $row['attributes'][$registrationId] = (int) $registered;
                        }
                    } elseif ((string) ($row['attributes'][$registrationId] ?? '') !== (string) ($source['attributes'][$registrationId] ?? '')) {
                        $registered = $row['attributes'][$registrationId] ?? null;
                        $row['attributes'][$statusId] = RegistrationStatus::fromBoolean($registered === null ? null : (bool) $registered);
                    }
                }
                if ($row['price'] === null) {
                    throw ValidationException::withMessages(['rows.'.$index.'.price' => 'قیمت این ردیف نامعتبر است.']);
                }
                if ($action === 'update') {
                    $updated += (int) $inventory->updatePrice($request->user(), $source, $row, 'rows.'.$index.'.action');

                    continue;
                }
                if (! $rules->canCreate($request->user())) {
                    throw ValidationException::withMessages(['rows.'.$index.'.action' => 'در حال حاضر ظرفیت ثبت آگهی جدید ندارید. ردیف‌های جدید را کنار بگذارید و دوباره تلاش کنید.']);
                }
                $upload = $request->file('uploads.'.$index);
                $mediaId = filled($request->input('media_ids.'.$index)) ? (int) $request->input('media_ids.'.$index) : null;
                if (! $upload && ! $mediaId) {
                    throw ValidationException::withMessages(['uploads.'.$index => 'برای این ردیف یک تصویر از رسانه‌ها انتخاب یا تصویر جدیدی آپلود کنید.']);
                }
                $createdListings[] = $service->create($request->user(), $row, $upload, $mediaId, $date, $rules, $imageService, $mediaService);
            }
            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            if ($exception instanceof ValidationException && ! $request->expectsJson()) {
                return redirect()->route('listings.import.create', ['preview' => $token])->withErrors($exception->errors())->withInput();
            }
            throw $exception;
        }
        $request->session()->forget('listing_import.preview.'.$token);
        collect($createdListings)->filter(fn ($listing): bool => $listing->status->value === 'pending')->each(
            fn ($listing) => User::query()->where('role', 'admin')->get()->each(fn (User $admin) => $admin->notify(new PendingListingNotification($listing))),
        );

        return redirect()->route('dashboard')->with('status', count($createdListings).' آگهی جدید ثبت شد، قیمت '.$updated.' آگهی به‌روزرسانی شد.');
    }

    private function parser(Request $request): ListingImportParser
    {
        $user = $request->user();
        abort_unless($user?->canImportListings(), 403);

        return $user->listingImportParser;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function withAttributeDefinitions(array $rows, User $user, CatalogColorMatcher $colors): array
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

        return array_map(function (array $row) use ($models, $latestPrimaryImages, $colors): array {
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

            $registration = collect($row['attribute_definitions'])->firstWhere('slug', 'registration_status');
            if ($registration !== null) {
                $detected = $row['attributes'][$registration['id']] ?? null;
                if (! in_array($detected, $registration['options'], true)) {
                    $row['attributes'][$registration['id']] = RegistrationStatus::fromBoolean($row['registered'] ?? null);
                }
            }

            $definitions = collect($row['attribute_definitions']);
            $color = $definitions->firstWhere('slug', 'color');
            if ($color) {
                $selected = $colors->match($row['raw'], $row['color'], $color['options'], $row['configured_color'] ?? null);
                if ($selected !== null) {
                    $row['attributes'][$color['id']] = $row['color'] = $selected;
                    $row['issues'] = array_values(array_diff($row['issues'], [CatalogColorMatcher::MISSING_COLOR_ISSUE]));
                } elseif ($row['color'] !== null) {
                    unset($row['attributes'][$color['id']]);
                    $row['color'] = null;
                    $row['issues'][] = 'رنگ متن با گزینه‌های کاتالوگ تطبیق قطعی ندارد؛ در پیش‌نمایش انتخاب کنید.';
                }
            }

            $part = $definitions->firstWhere('slug', 'part_number');
            if ($part && filled($row['part_number'] ?? null) && $part['options'] !== []) {
                $code = strtoupper($row['part_number']);
                $normalize = static fn ($option) => strtoupper(str_replace('/', '', $option));
                $options = collect($part['options']);
                $match = $options->first(fn ($option) => $normalize($option) === $code)
                    ?? $options->first(fn ($option) => $normalize($option) === $code.'A');
                if ($match !== null) {
                    $row['attributes'][$part['id']] = $match;
                }
            }

            $activation = $definitions->first(fn ($attribute) => $attribute['slug'] === 'activation_status' || $attribute['name'] === 'وضعیت(ACT/NOT)');
            if ($activation && filled($row['activation_status'] ?? null)) {
                $aliases = $row['activation_status'] === 'Active' ? ['active'] : ['notact', 'notactive'];
                $match = collect($activation['options'])->first(fn ($option) => in_array(preg_replace('/[^a-z]/', '', strtolower($option)), $aliases, true));
                if ($match !== null) {
                    $row['attributes'][$activation['id']] = $match;
                }
            }

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
