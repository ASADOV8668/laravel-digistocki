<x-app-layout title="پیش‌نمایش ایمپورت آگهی">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary"><x-heroicon-o-check-badge class="h-6 w-6" /></span>
                <div>
                    <p class="text-xs font-bold text-primary">{{ $parser->title }}</p>
                    <h1 class="mt-1 text-xl font-black text-neutral">پیش‌نمایش آگهی‌ها</h1>
                </div>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ count($rows) }} ردیف یکتا</span>
        </div>
    </x-slot>

    <section x-data="listingImportPreview(@js($rows), @js($colorOptions), @js(route('listings.media.index')) )" class="space-y-5 px-4 py-6">
        @if ($errors->any())
            <x-flowbite-alert type="danger">{{ $errors->first() }}</x-flowbite-alert>
        @endif
        <form method="POST" action="{{ route('listings.import.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            @php
                $states = [
                    'new' => 'آگهی جدید',
                    'price_change' => 'تغییر قیمت پیشنهادی',
                    'unchanged' => 'قیمت بدون تغییر',
                    'review' => 'نیاز به بررسی',
                    'unsupported' => 'ثبت نمی‌شود',
                ];
                $counts = collect($rows)->countBy(fn ($row) => $row['inventory_state'] ?? 'new');
            @endphp
            <div class="flex flex-wrap gap-2" aria-label="خلاصهٔ تطبیق موجودی">
                @foreach ($states as $state => $label)
                    @if ($counts->get($state))
                        <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ $counts->get($state) }} {{ $label }}</span>
                    @endif
                @endforeach
            </div>
            @if (!empty($unmatchedListings))
                <details class="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                    <summary class="cursor-pointer text-sm font-bold">آگهی‌های فعالی که در این لیست پیدا نشدند ({{ count($unmatchedListings) }})</summary>
                    <p class="mt-2 text-xs text-slate-500">موجودی این آگهی‌ها را جداگانه بررسی کنید. وضعیت آن‌ها با ایمپورت تغییر نمی‌کند.</p>
                    <ul class="mt-2 list-disc space-y-1 pr-5 text-sm">
                        @foreach ($unmatchedListings as $missing)
                            <li><a class="font-bold text-primary" href="{{ route('listings.edit', $missing['id']) }}">{{ $missing['title'] }}</a> <span class="text-xs text-slate-500">#{{ $missing['id'] }}</span></li>
                        @endforeach
                    </ul>
                </details>
            @endif
            <div class="flex flex-wrap items-end gap-4 rounded-lg bg-white p-4 shadow-sm dark:bg-slate-900">
                <div class="min-w-[220px] flex-1">
                    <label for="preview-listing-date" class="mb-2 block text-sm font-bold text-neutral dark:text-slate-100">تاریخ آگهی</label>
                    <input id="preview-listing-date" type="date" name="listing_date" value="{{ old('listing_date', $listingDate) }}" required class="w-full rounded-lg border-slate-200 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800">
                </div>
                <p class="text-xs leading-6 text-slate-500 dark:text-slate-400">مقادیر تشخیص‌داده‌شده را بررسی کنید و هر فیلدی را که لازم است اصلاح کنید. ردیف‌های خاکستری ثبت نمی‌شوند.</p>
            </div>

            <div class="space-y-4">
                @foreach ($rows as $index => $row)
                    @php
                        $state = $row['inventory_state'] ?? ($row['supported'] ? 'new' : 'unsupported');
                    @endphp
                    <article class="rounded-lg border {{ $row['supported'] ? 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900' : 'border-slate-200 bg-slate-50 opacity-75 dark:border-slate-800 dark:bg-slate-900/60' }} p-3 shadow-sm">
                        <input type="hidden" name="rows[{{ $index }}][enabled]" value="{{ $row['supported'] ? 1 : 0 }}">
                        <input type="hidden" name="rows[{{ $index }}][price]" :value="rows[{{ $index }}].price">
                        <input type="hidden" name="rows[{{ $index }}][color]" :value="rows[{{ $index }}].color || ''">
                        <input type="hidden" name="media_ids[{{ $index }}]" :value="rows[{{ $index }}].media_id || ''">
                        <div class="min-w-0">
                            <div class="flex items-start gap-3">
                                <div class="flex w-20 shrink-0 flex-col items-center gap-1.5">
                                    <template x-if="rows[{{ $index }}].media_url"><img :src="rows[{{ $index }}].media_url" alt="" class="h-16 w-16 rounded-lg object-cover"></template>
                                    <template x-if="!rows[{{ $index }}].media_url"><div class="flex h-16 w-16 items-center justify-center rounded-lg bg-slate-200 text-slate-400 dark:bg-slate-700"><x-heroicon-o-photo class="h-6 w-6" /></div></template>
                                    <button type="button" @click="openMedia({{ $index }})" class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-1 text-[10px] font-bold text-primary dark:bg-slate-800"><x-heroicon-o-photo class="h-3.5 w-3.5" />انتخاب</button>
                                    <label class="cursor-pointer text-center text-[10px] font-bold text-slate-500"><input type="file" name="uploads[{{ $index }}]" accept="image/*" capture="environment" class="sr-only" @change="uploadName({{ $index }}, $event)">تصویر جدید<span x-show="rows[{{ $index }}].upload_name" x-text="rows[{{ $index }}].upload_name" class="mt-0.5 block max-w-[78px] truncate text-primary"></span></label>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                        <span class="mb-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-black {{ $state === 'new' ? 'bg-success/10 text-success' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-200' }}">{{ $states[$state] }}</span>
                                            <h2 class="truncate text-left text-base font-black text-neutral dark:text-slate-100" dir="ltr">{{ $row['brand_name'] }} {{ $row['model_name'] }}</h2>
                                        </div>
                                    @if (!empty($row['issues']))
                                            <div class="max-w-md text-[11px] leading-5 text-warning">{{ implode(' ', $row['issues']) }}</div>
                                    @endif
                                    </div>
                                </div>
                            </div>
                            @if ($row['supported'])
                                @php
                                    $actions = ['skip' => 'رد کردن', 'new' => 'ثبت آگهی جدید'];
                                    if (isset($row['inventory_match'])) {
                                        $actions['update'] = 'به‌روزرسانی قیمت آگهی موجود';
                                    }
                                    $selectedAction = old("rows.$index.action", $state === 'new' ? 'new' : 'skip');
                                @endphp
                                <fieldset class="mt-3 flex flex-wrap gap-3 text-sm">
                                    <legend class="mb-1 text-xs font-bold text-slate-500">عملیات این ردیف</legend>
                                    @foreach ($actions as $action => $label)
                                        <label class="inline-flex items-center gap-1.5">
                                            <input type="radio" name="rows[{{ $index }}][action]" value="{{ $action }}" @checked($selectedAction === $action) class="border-slate-300 text-primary focus:ring-primary">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </fieldset>
                                @if (isset($row['inventory_match']))
                                    <p class="mt-2 text-xs text-slate-500">{{ $row['inventory_match']['title'] }} #{{ $row['inventory_match']['id'] }}، قیمت فعلی {{ $row['inventory_match']['price_on_request'] ? 'تماس بگیرید' : number_format($row['inventory_match']['price']) . ' تومان' }}</p>
                                @elseif ($state === 'review')
                                    <p class="mt-2 text-xs text-slate-500">برای تطبیق مطمئن، مشخصات کافی نیست یا چند گوشی مشابه پیدا شد.</p>
                                @endif
                            @endif
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <label class="block"><span class="mb-1 block text-xs font-bold text-slate-500">قیمت (تومان)</span><input type="text" inputmode="numeric" dir="ltr" x-model="rows[{{ $index }}].price" class="w-full rounded-lg border-slate-200 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800"></label>
                                    <label class="block"><span class="mb-1 block text-xs font-bold text-slate-500">توضیحات</span><input type="text" name="rows[{{ $index }}][description]" value="{{ old("rows.$index.description", $row['description']) }}" class="w-full rounded-lg border-slate-200 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800"></label>
                            </div>
                                <div x-show="rows[{{ $index }}].attribute_definitions.length" class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <template x-for="attribute in visibleAttributes(rows[{{ $index }}])" :key="`import-attribute-${attribute.id}`">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                                            <div class="mb-2 flex items-center justify-between gap-2">
                                                <span class="text-xs font-black text-neutral dark:text-slate-100" x-text="attribute.name"></span>
                                                <span x-show="attribute.unit" class="text-[10px] font-bold text-slate-400" x-text="attribute.unit"></span>
                                            </div>
                                            <template x-if="attribute.slug === 'registered' && attribute.type === 'boolean'">
                                                <div role="group" aria-label="وضعیت رجیستری" class="flex flex-wrap items-center gap-1.5">
                                                    <template x-for="option in [{value: '1', text: 'رجیستر شده'}, {value: '0', text: 'رجیستر نشده'}, {value: '', text: 'نامشخص'}]" :key="option.value">
                                                        <button type="button"
                                                            @click="setAttributeValue({{ $index }}, attribute, option.value)"
                                                            :aria-pressed="(attributeValue(rows[{{ $index }}], attribute) === option.value).toString()"
                                                            :class="attributeValue(rows[{{ $index }}], attribute) === option.value ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200'"
                                                            class="rounded-lg border px-2.5 py-1.5 text-xs font-bold" x-text="option.text"></button>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="attribute.slug !== 'registered' && attribute.type === 'boolean'">
                                                <button type="button"
                                                    @click="setAttributeValue({{ $index }}, attribute, attributeValue(rows[{{ $index }}], attribute) === '1' ? '' : '1')"
                                                    :aria-pressed="(attributeValue(rows[{{ $index }}], attribute) === '1').toString()"
                                                    :class="attributeValue(rows[{{ $index }}], attribute) === '1' ? 'border-primary bg-primary text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-primary/50 hover:bg-primary-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200'"
                                                    class="rounded-lg border px-3 py-1.5 text-xs font-bold transition">
                                                    دارد
                                                </button>
                                            </template>
                                            <template x-if="attribute.type !== 'boolean' && attribute.options.length">
                                                <div class="flex max-h-28 flex-wrap gap-1.5 overflow-y-auto">
                                                    <template x-for="option in attribute.options" :key="`${attribute.id}-${option}`">
                                                        <button type="button"
                                                            @click="toggleAttribute({{ $index }}, attribute, option)"
                                                            :aria-pressed="isAttributeSelected(rows[{{ $index }}], attribute, option).toString()"
                                                            :class="isAttributeSelected(rows[{{ $index }}], attribute, option) ? 'border-primary bg-primary text-white shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-primary/50 hover:bg-primary-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200'"
                                                            class="rounded-lg border px-2.5 py-1.5 text-xs font-bold transition">
                                                            <span x-text="option"></span>
                                                            <span x-show="attribute.unit && attribute.slug !== 'color'" x-text="attribute.unit"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="attribute.type !== 'boolean' && (!attribute.options.length || ['storage', 'battery_health', 'color'].includes(attribute.slug))">
                                                <input type="text" :aria-label="attribute.name"
                                                    :inputmode="['storage', 'battery_health'].includes(attribute.slug) ? 'numeric' : 'text'"
                                                    :value="attributeValue(rows[{{ $index }}], attribute)"
                                                    @input="setAttributeValue({{ $index }}, attribute, $event.target.value)"
                                                    class="mt-2 w-full rounded-lg border-slate-200 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900">
                                            </template>
                                            <template x-if="attribute.type === 'multi_select'">
                                                <template x-for="value in attributeValues(rows[{{ $index }}], attribute)" :key="`${attribute.id}-hidden-${value}`">
                                                    <input type="hidden" :name="`rows[{{ $index }}][attributes][${attribute.id}][]`" :value="value">
                                                </template>
                                            </template>
                                            <template x-if="attribute.type !== 'multi_select'">
                                                <input type="hidden" :name="`rows[{{ $index }}][attributes][${attribute.id}]`" :value="attributeValue(rows[{{ $index }}], attribute)">
                                            </template>
                                        </div>
                                    </template>
                                </div>
                                <p class="mt-3 rounded-lg bg-slate-50 p-3 text-xs leading-6 text-slate-500 dark:bg-slate-800 dark:text-slate-300" dir="ltr">{{ $row['raw'] }}</p>
                            </div>
                    </article>
                @endforeach
            </div>
            <button class="w-full rounded-lg bg-primary px-5 py-4 text-sm font-black text-white shadow-lg shadow-primary/20 transition hover:bg-primary-600">اعمال عملیات انتخاب‌شده</button>
        </form>

        <div x-show="mediaOpen" x-cloak class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/60 p-4 sm:items-center" @keydown.escape.window="mediaOpen = false">
            <div @click.outside="mediaOpen = false" class="max-h-[85vh] w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-2xl dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><h2 class="font-black text-neutral dark:text-white">انتخاب از رسانه‌های قبلی</h2><button type="button" @click="mediaOpen = false" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><x-heroicon-o-x-mark class="h-5 w-5" /></button></div>
                <div class="grid max-h-[60vh] grid-cols-3 gap-3 overflow-y-auto p-4 sm:grid-cols-5"><template x-for="media in mediaItems" :key="media.id"><button type="button" @click="selectMedia(media)" class="overflow-hidden rounded-lg border-2 border-transparent bg-slate-50 p-1 hover:border-primary dark:bg-slate-800"><img :src="media.thumbnail_url || media.url" alt="" class="aspect-square w-full rounded object-cover"></button></template><p x-show="!mediaItems.length" class="col-span-full py-10 text-center text-sm text-slate-500">رسانه‌ای پیدا نشد.</p></div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            function listingImportPreview(initialRows, colorOptions, mediaEndpoint) {
                return {
                    rows: initialRows.map(row => ({...row, attributes: row.attributes || {}, attribute_definitions: row.attribute_definitions || [], media_id: row.media_id || null, media_url: row.media_url || '', upload_name: ''})),
                    colorOptions, mediaEndpoint, mediaItems: [], mediaOpen: false, activeRow: null,
                    visibleAttributes(row) {
                        const hasRegistrationStatus = row.attribute_definitions.some(attribute => attribute.slug === 'registration_status');
                        return row.attribute_definitions.filter(attribute => !hasRegistrationStatus || attribute.slug !== 'registered');
                    },
                    attributeValues(row, attribute) {
                        const value = row.attributes?.[attribute.id] ?? '';
                        return Array.isArray(value) ? value.map(String) : (value === '' || value === null ? [] : [String(value)]);
                    },
                    attributeValue(row, attribute) {
                        return this.attributeValues(row, attribute)[0] || '';
                    },
                    isAttributeSelected(row, attribute, option) {
                        return this.attributeValues(row, attribute).includes(String(option));
                    },
                    setAttributeValue(index, attribute, value) {
                        this.rows[index].attributes = {...this.rows[index].attributes, [attribute.id]: value};
                    },
                    toggleAttribute(index, attribute, option) {
                        const row = this.rows[index];
                        const current = this.attributeValues(row, attribute);
                        const normalized = String(option);
                        const values = attribute.type === 'multi_select'
                            ? (current.includes(normalized) ? current.filter(value => value !== normalized) : [...current, normalized])
                            : [normalized];
                        this.setAttributeValue(index, attribute, attribute.type === 'multi_select' ? values : values[0]);
                    },
                    async openMedia(index) {
                        this.activeRow = index; this.mediaOpen = true;
                        if (this.mediaItems.length) return;
                        const response = await fetch(mediaEndpoint, {headers: {'Accept': 'application/json'}});
                        if (response.ok) this.mediaItems = (await response.json()).media || [];
                    },
                    selectMedia(media) {
                        this.rows[this.activeRow].media_id = media.id;
                        this.rows[this.activeRow].media_url = media.thumbnail_url || media.url;
                        this.mediaOpen = false;
                    },
                    uploadName(index, event) {
                        this.rows[index].media_id = null; this.rows[index].media_url = '';
                        this.rows[index].upload_name = event.target.files[0]?.name || '';
                    }
                };
            }
        </script>
    @endpush
</x-app-layout>
