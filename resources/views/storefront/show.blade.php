@php
    $siteTitle = app(\App\Services\SystemOptions::class)->get('site_title');
    $storeSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $store->name,
        'url' => route('storefront.show', $store),
    ];

    if ($store->logo_path) {
        $storeSchema['image'] = asset('storage/' . $store->logo_path);
        $storeSchema['logo'] = asset('storage/' . $store->logo_path);
    }

    if ($store->contact_phone) {
        $storeSchema['telephone'] = $store->contact_phone;
    }

    if ($store->address || $store->city || $store->province) {
        $storeSchema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $store->address,
            'addressLocality' => $store->city?->name,
            'addressRegion' => $store->province?->name,
            'addressCountry' => 'IR',
        ];
    }

    $socialLinks = collect([
        'اینستاگرام' => \App\Support\SocialHandle::url($store->instagram_url, 'instagram'),
        'تلگرام' => \App\Support\SocialHandle::url($store->telegram_url, 'telegram'),
        'واتساپ' => \App\Support\SocialHandle::url($store->whatsapp_url, 'whatsapp'),
        'بله' => \App\Support\SocialHandle::url($store->bale_url, 'bale'),
        'روبیکا' => \App\Support\SocialHandle::url($store->rubika_url, 'rubika'),
    ])->filter();
@endphp

@push('head')
    <meta name="description"
        content="{{ $store->name }}؛ مشاهده اطلاعات فروشگاه و آخرین آگهی‌های فعال فروشنده در {{ $siteTitle }}.">
    <script type="application/ld+json">{!! json_encode($storeSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

<x-app-layout :title="$store->name">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('listings.index') }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100"
                aria-label="بازگشت">
                <x-heroicon-o-arrow-right class="h-5 w-5" />
            </a>
            <div>
                <p class="text-[11px] font-bold text-primary">فروشگاه فروشنده</p>
                <h1 class="mt-1 text-xl font-black text-neutral">{{ $store->name }}</h1>
            </div>
        </div>
    </x-slot>

    <section x-data="listingSearch(
        @js(route('storefront.search.suggestions', $store)),
        @js(route('storefront.models', $store)),
        @js(route('storefront.models', $store)),
        @js(''),
        @js($selectedModel?->id),
        @js($filterAttributesPayload),
        @js(request()->input('filters', [])),
        @js(''),
        @js(''),
        @js(request('q')),
        @js($selectedModel?->brand_id ?? request('brand_id')),
        @js(request('min_price')),
        @js(request('max_price')),
        @js($sort),
        @js($priceCeiling ?: 1),
        @js([]),
        @js($priceFloor),
        @js($activeOnly)
    )" class="space-y-6 px-4 py-6">
        <div class="overflow-hidden rounded-lg bg-neutral p-5 text-white shadow-xl shadow-slate-900/10">
            <div class="flex items-start gap-4">
                @if ($store->logo_path)
                    <img src="{{ asset('storage/' . $store->logo_path) }}" alt="{{ $store->name }}"
                        class="h-20 w-20 rounded-lg bg-white object-cover p-1">
                @else
                    <div
                        class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-primary text-3xl font-black">
                        {{ mb_substr($store->name, 0, 1) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="text-xs font-bold text-primary-100">فروشگاه فعال</p>
                    <h2 class="mt-1 text-2xl font-black">{{ $store->name }}</h2>
                    @if ($store->province || $store->city)
                        <p class="mt-2 flex items-center gap-1 text-xs text-white/65">
                            <x-heroicon-o-map-pin class="h-4 w-4" />
                            {{ collect([$store->province?->name, $store->city?->name])->filter()->implode('، ') }}
                        </p>
                    @endif
                </div>
            </div>

            <div x-data="{ contactOpen: false }" class="mt-5 border-t border-white/10 pt-4">
                @auth
                    <button type="button" @click="contactOpen = !contactOpen" :aria-expanded="contactOpen.toString()"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-xs font-black text-white transition hover:bg-primary-600">
                        <x-heroicon-o-phone class="h-4 w-4" />
                        <span x-text="contactOpen ? 'بستن اطلاعات تماس' : 'اطلاعات تماس فروشگاه'">اطلاعات تماس
                            فروشگاه</span>
                    </button>
                    <div x-show="contactOpen" x-cloak class="mt-4 grid gap-3 text-xs text-white/80 sm:grid-cols-2">
                        @if ($store->contact_phone)
                            <a href="tel:{{ $store->contact_phone }}"
                                class="flex items-center gap-2 text-white transition hover:text-primary-100">
                                <x-heroicon-o-phone class="h-4 w-4" />
                                {{ $store->contact_phone }}
                            </a>
                        @endif
                        @if ($store->address)
                            <p class="flex items-start gap-2">
                                <x-heroicon-o-building-office-2 class="mt-0.5 h-4 w-4 shrink-0" />
                                {{ $store->address }}
                            </p>
                        @endif
                        @foreach ($socialLinks as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                class="flex items-center gap-2 text-white transition hover:text-primary-100">
                                <x-heroicon-o-link class="h-4 w-4" />
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-white/20">
                        <x-heroicon-o-lock-closed class="h-4 w-4" />
                        برای مشاهده اطلاعات تماس وارد شوید
                    </a>
                @endauth
            </div>

            <div x-data="shareLink(@js(route('storefront.show', $store)))" class="mt-5 flex flex-wrap gap-2 border-t border-white/10 pt-4">
                <button type="button" @click="share()"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-xs font-black text-white transition hover:bg-primary-600">
                    <x-heroicon-o-share class="h-4 w-4" />
                    اشتراک‌گذاری فروشگاه
                </button>
                <button type="button" @click="copy()"
                    class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-white/15">
                    <x-heroicon-o-clipboard-document class="h-4 w-4" />
                    <span x-text="copied ? 'لینک کپی شد' : 'کپی لینک'">کپی لینک</span>
                </button>
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold text-primary">ویترین فروشگاه</p>
                    <h2 class="mt-1 text-lg font-black text-neutral">آخرین آگهی‌ها</h2>
                </div>
                <span class="rounded-full bg-primary/10 px-3 py-1.5 text-[11px] font-bold text-primary">
                    {{ number_format($listings->total()) }} آگهی
                </span>
            </div>

            <button type="button" data-drawer-target="storefront-filters" data-drawer-show="storefront-filters"
                data-drawer-placement="left" @click="filtersOpen = true" :aria-expanded="filtersOpen.toString()"
                aria-controls="storefront-filters" aria-label="باز کردن فیلترهای فروشگاه"
                class="fixed bottom-24 left-4 z-30 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-white shadow-xl shadow-primary/30 transition hover:bg-primary-600 focus:outline-none focus:ring-4 focus:ring-primary/30 md:bottom-8 md:left-8">
                <x-heroicon-o-adjustments-horizontal class="h-6 w-6" />
                <span x-show="activeFilterCount()" x-cloak
                    class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-neutral px-1 text-[10px] font-black text-white"
                    x-text="activeFilterCount()"></span>
            </button>

            <div x-show="filtersOpen" x-cloak @click="filtersOpen = false" class="fixed inset-0 z-40 bg-slate-950/40">
            </div>
            <aside id="storefront-filters" data-drawer-backdrop="true" data-drawer-body-scrolling="false" role="region"
                aria-labelledby="storefront-filters-title" @keydown.escape.window="filtersOpen = false"
                :class="filtersOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-50 w-[min(92vw,24rem)] max-w-full overflow-y-auto bg-slate-50 p-4 transition-transform duration-300">
                <div class="min-h-full rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 id="storefront-filters-title" class="font-black text-neutral">فیلتر آگهی‌های فروشگاه
                            </h2>
                            <p class="mt-1 text-[11px] text-slate-400">فقط اطلاعات موجود در همین فروشگاه</p>
                        </div>
                        <button type="button" data-drawer-hide="storefront-filters" @click="filtersOpen = false"
                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="بستن">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    <form method="GET" action="{{ route('storefront.show', $store) }}" role="search"
                        aria-label="فیلتر آگهی‌های فروشگاه" @submit.prevent="applyFilters($event)" class="space-y-4">
                        <div class="relative">
                            <x-heroicon-o-magnifying-glass
                                class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                            <label for="storefront-search" class="sr-only">جستجو در آگهی‌های فروشگاه</label>
                            <input id="storefront-search" name="q" x-model="query" @input="search"
                                @focus="query.length >= 2 && (open = true)" @keydown.escape="open = false"
                                type="search" autocomplete="off" placeholder="مثلاً آیفون یا iPhone 13"
                                class="public-input min-w-0">
                            <div x-show="open" x-cloak @click.outside="open = false"
                                class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                                <div x-show="loading" class="px-3 py-3 text-xs text-slate-500">در حال جستجو...</div>
                                <template x-if="!loading && suggestions.brands.length">
                                    <div>
                                        <p class="px-3 pb-1 pt-2 text-[11px] font-bold text-slate-400">برندها</p>
                                        <template x-for="brand in suggestions.brands" :key="`store-brand-${brand.id}`">
                                            <button type="button" @click="selectBrand(brand)"
                                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-right text-sm hover:bg-slate-50">
                                                <span x-text="brand.label" class="font-bold text-neutral"></span>
                                                <span x-text="brand.secondary" class="text-xs text-slate-400"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!loading && suggestions.models.length">
                                    <div>
                                        <p class="px-3 pb-1 pt-2 text-[11px] font-bold text-slate-400">مدل‌ها</p>
                                        <template x-for="model in suggestions.models" :key="`store-model-${model.id}`">
                                            <button type="button" @click="selectModel(model)"
                                                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-right text-sm hover:bg-slate-50">
                                                <span><span x-text="model.label"
                                                        class="font-bold text-neutral"></span><span
                                                        x-text="model.brand ? ` · ${model.brand}` : ''"
                                                        class="mr-1 text-xs text-slate-400"></span></span>
                                                <span x-text="model.secondary" class="text-xs text-slate-400"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="!loading && !suggestions.brands.length && !suggestions.models.length"
                                    class="px-3 py-3 text-xs text-slate-500">موردی پیدا نشد.</div>
                            </div>
                        </div>

                        <div>
                            <label for="storefront-brand"
                                class="mb-1 block text-xs font-bold text-slate-600">برند</label>
                            <select id="storefront-brand" name="brand_id" x-model="selectedBrandId"
                                @change="selectBrandId($event.target.value)" class="public-select">
                                <option value="">همه برندهای این فروشگاه</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }} ({{ $brand->name_en }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="storefront-model" class="mb-1 block text-xs font-bold text-slate-600">مدل
                                گوشی</label>
                            <select id="storefront-model" name="phone_model_id" x-model="selectedModelId"
                                @change="selectModelId($event.target.value)"
                                :disabled="!selectedBrandId || modelsLoading" class="public-select">
                                <option value="">همه مدل‌ها</option>
                                <template x-for="model in models" :key="model.id">
                                    <option :value="model.id"
                                        x-text="`${model.brand} · ${model.label}${model.secondary ? ` / ${model.secondary}` : ''}`">
                                    </option>
                                </template>
                            </select>
                            <span x-show="modelsLoading" class="mt-1 block text-[10px] text-slate-400">در حال بارگذاری
                                مدل‌ها...</span>
                        </div>

                        <label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-xs font-bold text-slate-600">
                            <input type="hidden" name="active_only" x-bind:value="activeOnly ? '1' : '0'">
                            <input type="checkbox" x-model="activeOnly" @checked($activeOnly)
                                class="public-check">
                            فقط نمایش آگهی‌های فعال
                        </label>

                        @if ($hasPriceRange)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <span class="text-xs font-bold text-slate-600">بازه قیمت</span>
                                    <span class="text-[10px] font-bold text-slate-400">تومان</span>
                                </div>
                                <div data-price-range="storefront" dir="ltr"
                                    class="mb-3 flex items-center justify-between gap-3 text-[11px] font-black text-primary">
                                    <span x-text="`${formatPrice(minPrice)} تومان`">{{ number_format($priceFloor) }}
                                        تومان</span>
                                    <span x-text="`${formatPrice(maxPrice)} تومان`">{{ number_format($priceCeiling) }}
                                        تومان</span>
                                </div>
                                <div class="relative h-6" dir="ltr">
                                    <div class="absolute left-0 right-0 top-2 h-2 rounded-full bg-slate-200"></div>
                                    <div class="absolute top-2 h-2 rounded-full bg-primary"
                                        :style="`left: ${((minPrice - priceFloor) / ((priceCeiling - priceFloor) || 1)) * 100}%; right: ${100 - ((maxPrice - priceFloor) / ((priceCeiling - priceFloor) || 1)) * 100}%;`">
                                    </div>
                                    <label for="storefront-min-price" class="sr-only">حداقل قیمت</label>
                                    <input id="storefront-min-price" type="range" :min="priceFloor"
                                        :max="priceCeiling" :step="priceStep" x-model.number="minPrice"
                                        @input="syncPriceRange('min')"
                                        class="price-range absolute inset-0 h-6 w-full">
                                    <label for="storefront-max-price" class="sr-only">حداکثر قیمت</label>
                                    <input id="storefront-max-price" type="range" :min="priceFloor"
                                        :max="priceCeiling" :step="priceStep" x-model.number="maxPrice"
                                        @input="syncPriceRange('max')"
                                        class="price-range absolute inset-0 h-6 w-full">
                                </div>
                                <input type="hidden" name="min_price"
                                    x-bind:value="minPrice > priceFloor ? minPrice : ''">
                                <input type="hidden" name="max_price"
                                    x-bind:value="maxPrice < priceCeiling ? maxPrice : ''">
                                <p class="mt-2 text-[10px] text-slate-400">بازه قیمت فقط از آگهی‌های قیمت‌دار همین
                                    فروشگاه محاسبه شده است.</p>
                            </div>
                        @endif

                        <div>
                            <label for="storefront-sort"
                                class="mb-1 block text-xs font-bold text-slate-600">مرتب‌سازی</label>
                            <select id="storefront-sort" name="sort" x-model="sort" class="public-select">
                                <option value="newest">جدیدترین</option>
                                <option value="price_asc">ارزان‌ترین</option>
                                <option value="price_desc">گران‌ترین</option>
                                <option value="views">پربازدیدترین</option>
                            </select>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-black text-neutral">ویژگی‌های موجود</h3>
                                <span x-show="attributesLoading" class="text-[11px] text-slate-400">در حال
                                    بارگذاری...</span>
                            </div>
                            <p x-show="!selectedModelId" class="text-xs leading-6 text-slate-500">ابتدا برند و مدل را
                                انتخاب کنید تا فقط ویژگی‌های موجود همان مدل در این فروشگاه نمایش داده شود.</p>
                            <div x-show="selectedModelId && !attributesLoading" class="space-y-3">
                                <template x-for="attribute in attributes" :key="attribute.id">
                                    <label class="block text-xs font-bold text-slate-600">
                                        <span class="mb-1 block">
                                            <span x-text="attribute.name"></span>
                                            <span x-show="attribute.unit" x-text="` (${attribute.unit})`"
                                                class="mr-1 text-[10px] font-normal text-slate-400"></span>
                                        </span>
                                        <template x-if="attribute.slug === 'color'">
                                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                                                <input type="hidden" :name="`filters[${attribute.id}]`"
                                                    x-model="filters[attribute.id]">
                                                <div class="flex flex-wrap gap-1.5">
                                                    <template x-for="option in attribute.options"
                                                        :key="option">
                                                        <button type="button" @click="filters[attribute.id] = option"
                                                            class="inline-flex items-center gap-1 rounded-md border border-transparent px-2 py-1 text-[11px] font-bold text-neutral hover:border-primary/30"
                                                            :class="filters[attribute.id] === option ?
                                                                'border-primary bg-white ring-2 ring-primary/20' : ''">
                                                            <span
                                                                class="h-3.5 w-3.5 rounded-full border border-slate-300"
                                                                :style="`background-color: ${colorHex(option)}`"></span>
                                                            <span x-text="option"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="attribute.type === 'boolean'">
                                            <span
                                                class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 font-normal">
                                                <input type="checkbox" :name="`filters[${attribute.id}]`"
                                                    value="1" x-model="filters[attribute.id]"
                                                    class="public-check">
                                                دارد
                                            </span>
                                        </template>
                                        <template x-if="attribute.type === 'select' && attribute.slug !== 'color'">
                                            <select :name="`filters[${attribute.id}]`" x-model="filters[attribute.id]"
                                                class="public-select font-normal">
                                                <option value="">همه</option>
                                                <template x-for="option in attribute.options" :key="option">
                                                    <option :value="option" x-text="option"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="attribute.type === 'multi_select'">
                                            <select multiple :name="`filters[${attribute.id}][]`"
                                                x-model="filters[attribute.id]" x-init="filters[attribute.id] = Array.isArray(filters[attribute.id]) ? filters[attribute.id] : (filters[attribute.id] ? [filters[attribute.id]] : [])"
                                                class="public-select min-h-28 font-normal">
                                                <template x-for="option in attribute.options" :key="option">
                                                    <option :value="option" x-text="option"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="attribute.type === 'integer' || attribute.type === 'decimal'">
                                            <input :name="`filters[${attribute.id}]`" x-model="filters[attribute.id]"
                                                type="number" :step="attribute.type === 'decimal' ? '0.01' : '1'"
                                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-neutral shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/30">
                                        </template>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button class="mobile-button flex-1 bg-neutral text-white hover:bg-neutral-800">اعمال
                                فیلتر</button>
                            <button type="button" @click="reset"
                                class="rounded-lg px-3 text-xs font-bold text-slate-500 hover:bg-slate-50">پاک
                                کردن</button>
                        </div>
                    </form>
                </div>
            </aside>

            <div class="min-w-0 pb-28" @click="paginateResults($event)">
                <div id="listing-results" :aria-busy="resultsLoading.toString()" aria-live="polite"
                    aria-atomic="false">
                    @include('storefront.partials.results', [
                        'listings' => $listings,
                        'sort' => $sort,
                        'activeFilters' => $activeFilters,
                        'store' => $store,
                    ])
                </div>
                <div x-show="resultsLoading" x-cloak
                    class="pointer-events-none fixed inset-x-4 top-20 z-40 mx-auto max-w-md rounded-lg bg-neutral px-4 py-3 text-xs font-bold text-white shadow-xl">
                    <x-flowbite-loading label="در حال به‌روزرسانی نتایج..." />
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
