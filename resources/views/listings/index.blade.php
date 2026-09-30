<x-app-layout title="جستجوی آگهی‌ها">
    <x-slot name="header"><h1 class="text-xl font-black text-neutral">جستجوی آگهی‌ها</h1></x-slot>

    <section x-data='listingSearch(@js(route("listings.search.suggestions")), @js(url("/listings/models")), @js(url("/listings/models")), @js(url("/locations/provinces")), @js($selectedModel?->id), @js($filterAttributesPayload), @js(request()->input("filters", [])), @js(request("province_id")), @js(request("city_id")), @js(request("q")), @js(request("brand_id")), @js(request("min_price")), @js(request("max_price")), @js($sort))' class="space-y-6 px-4 py-6">
        <div class="flex items-end justify-between gap-3"><div><p class="text-xs font-bold text-primary">بازار موبایل</p><h1 class="mt-1 text-2xl font-black text-neutral">جستجوی آگهی‌ها</h1><p class="mt-1 text-xs text-slate-400">مدل و ویژگی‌های مناسب خودت را مرحله‌به‌مرحله انتخاب کن.</p></div><span class="rounded-full bg-primary-50 px-3 py-1.5 text-[11px] font-black text-primary">{{ number_format($listings->total()) }} نتیجه</span></div>
        <button type="button" @click="filtersOpen = true" :aria-expanded="filtersOpen.toString()" aria-controls="listing-filters" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-neutral px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-neutral-800 md:hidden">
            <x-heroicon-o-adjustments-horizontal class="h-5 w-5" /> فیلتر و جستجو
        </button>

        @if ($selectedModel)
            <div class="flex items-center justify-between gap-3 rounded-2xl border border-primary/15 bg-primary-50 p-4"><div class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-primary"><x-heroicon-o-device-phone-mobile class="h-5 w-5" /></span><div class="min-w-0"><p class="text-[11px] font-bold text-primary">مدل انتخاب‌شده</p><p class="truncate text-sm font-black text-neutral">{{ $selectedModel->brand->name }} {{ $selectedModel->name_fa ?: $selectedModel->name }}</p></div></div><a href="{{ route('listings.index') }}" class="shrink-0 rounded-xl bg-white px-3 py-2 text-[11px] font-bold text-slate-600 shadow-sm">پاک کردن</a></div>
        @endif

        <div class="grid gap-6 md:grid-cols-[18rem_minmax(0,1fr)]">
            <div x-show="filtersOpen" x-cloak @click="filtersOpen = false" class="fixed inset-0 z-40 bg-slate-950/40 md:hidden"></div>

            <aside id="listing-filters" role="region" aria-labelledby="listing-filters-title" aria-label="فیلتر آگهی‌ها" @keydown.escape.window="filtersOpen = false" :class="filtersOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'" class="fixed inset-y-0 left-0 z-50 w-full max-w-md overflow-y-auto bg-slate-50 p-4 transition-transform md:static md:z-auto md:block md:max-w-none md:translate-x-0 md:rounded-3xl md:bg-transparent md:p-0">
                <div class="rounded-3xl bg-white p-4 shadow-sm ring-1 ring-slate-100 md:sticky md:top-6">
                    <div class="mb-4 flex items-center justify-between">
                        <div><h2 id="listing-filters-title" class="font-black text-neutral">فیلتر آگهی‌ها</h2><p class="mt-1 text-[11px] text-slate-400">انتخاب مرحله‌ای ویژگی‌ها</p></div>
                        <button type="button" @click="filtersOpen = false" class="rounded-xl p-2 text-slate-500 md:hidden" aria-label="بستن"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                    </div>

                    <form method="GET" action="{{ route('listings.index') }}" role="search" aria-label="جستجوی آگهی‌ها" @submit.prevent="applyFilters($event)" class="space-y-4">
                        <div class="relative">
                            <x-heroicon-o-magnifying-glass class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                            <label for="listing-search" class="sr-only">جستجوی برند یا مدل</label>
                            <input id="listing-search" name="q" x-model="query" @input="search" @focus="query.length >= 2 && (open = true)" @keydown.escape="open = false" type="search" autocomplete="off" placeholder="مثلاً آیفون یا iPhone 13" class="w-full rounded-2xl border-0 bg-slate-50 py-3 pr-10 pl-3 text-sm ring-1 ring-slate-100 focus:ring-primary" />
                            <div x-show="open" x-cloak @click.outside="open = false" class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 overflow-y-auto rounded-2xl bg-white p-2 shadow-xl ring-1 ring-slate-100">
                                <div x-show="loading" class="px-3 py-3 text-xs text-slate-500">در حال جستجو...</div>
                                <template x-if="!loading && suggestions.brands.length">
                                    <div>
                                        <p class="px-3 pb-1 pt-2 text-[11px] font-bold text-slate-400">برندها</p>
                                        <template x-for="brand in suggestions.brands" :key="`brand-${brand.id}`">
                                            <button type="button" @click="selectBrand(brand)" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-right text-sm hover:bg-slate-50">
                                                <span x-text="brand.label" class="font-bold text-neutral"></span><span x-text="brand.secondary" class="text-xs text-slate-400"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!loading && suggestions.models.length">
                                    <div>
                                        <p class="px-3 pb-1 pt-2 text-[11px] font-bold text-slate-400">مدل‌ها</p>
                                        <template x-for="model in suggestions.models" :key="`model-${model.id}`">
                                            <button type="button" @click="selectModel(model)" class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-right text-sm hover:bg-slate-50">
                                                <span><span x-text="model.label" class="font-bold text-neutral"></span><span x-text="model.brand ? ` · ${model.brand}` : ''" class="mr-1 text-xs text-slate-400"></span></span><span x-text="model.secondary" class="text-xs text-slate-400"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="!loading && !suggestions.brands.length && !suggestions.models.length" class="px-3 py-3 text-xs text-slate-500">موردی پیدا نشد.</div>
                            </div>
                        </div>

                        <input type="hidden" name="brand_id" x-model="selectedBrandId" />
                        <div>
                            <label for="listing-model" class="mb-1 block text-xs font-bold text-slate-600">مدل گوشی</label>
                            <select id="listing-model" name="phone_model_id" x-model="selectedModelId" @change="selectModelId($event.target.value)" :disabled="modelsLoading" class="w-full rounded-2xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary disabled:bg-slate-100">
                                <option value="">همه مدل‌ها</option>
                                <template x-for="model in models" :key="model.id">
                                    <option :value="model.id" x-text="`${model.brand} · ${model.label}${model.secondary ? ` / ${model.secondary}` : ''}`"></option>
                                </template>
                            </select>
                            <span x-show="modelsLoading" class="mt-1 block text-[10px] text-slate-400">در حال بارگذاری مدل‌ها...</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="listing-province" class="mb-1 block text-xs font-bold text-slate-600">استان</label>
                                <select id="listing-province" name="province_id" x-model="provinceId" @change="loadCities()" class="w-full rounded-2xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary">
                                    <option value="">همه استان‌ها</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="listing-city" class="mb-1 block text-xs font-bold text-slate-600">شهر</label>
                                <select id="listing-city" name="city_id" x-model="cityId" :disabled="!provinceId || citiesLoading" class="w-full rounded-2xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary disabled:bg-slate-100">
                                    <option value="">همه شهرها</option>
                                    <template x-for="city in cities" :key="city.id">
                                        <option :value="city.id" x-text="city.name"></option>
                                    </template>
                                </select>
                                <span x-show="citiesLoading" class="mt-1 block text-[10px] text-slate-400">در حال بارگذاری شهرها...</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div><label for="listing-min-price" class="sr-only">حداقل قیمت</label><input id="listing-min-price" name="min_price" x-model="minPrice" type="number" min="0" placeholder="حداقل قیمت" class="w-full rounded-xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary" /></div>
                            <div><label for="listing-max-price" class="sr-only">حداکثر قیمت</label><input id="listing-max-price" name="max_price" x-model="maxPrice" type="number" min="0" placeholder="حداکثر قیمت" class="w-full rounded-xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary" /></div>
                        </div>

                        <div>
                            <label for="listing-sort" class="mb-1 block text-xs font-bold text-slate-600">مرتب‌سازی</label>
                            <select id="listing-sort" name="sort" x-model="sort" class="w-full rounded-2xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary">
                                <option value="newest">جدیدترین</option>
                                <option value="price_asc">ارزان‌ترین</option>
                                <option value="price_desc">گران‌ترین</option>
                                <option value="views">پربازدیدترین</option>
                            </select>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-black text-neutral">ویژگی‌های مدل</h3>
                                <span x-show="attributesLoading" class="text-[11px] text-slate-400">در حال بارگذاری...</span>
                            </div>
                            <p x-show="!selectedModelId" class="text-xs leading-6 text-slate-500">ابتدا برند یا مدل را از پیشنهادها انتخاب کنید تا ویژگی‌های همان مدل نمایش داده شود.</p>
                            <div x-show="selectedModelId && !attributesLoading" class="space-y-3">
                                <template x-for="attribute in attributes" :key="attribute.id">
                                    <label class="block text-xs font-bold text-slate-600">
                                        <span class="mb-1 block"><span x-text="attribute.name"></span><span x-show="attribute.unit" x-text="` (${attribute.unit})`" class="mr-1 text-[10px] font-normal text-slate-400"></span></span>
                                        <template x-if="attribute.type === 'boolean'">
                                            <span class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 font-normal ring-1 ring-slate-100"><input type="checkbox" :name="`filters[${attribute.id}]`" value="1" x-model="filters[attribute.id]" class="rounded border-slate-300 text-primary focus:ring-primary" /> دارد</span>
                                        </template>
                                        <template x-if="attribute.type === 'select'">
                                            <select :name="`filters[${attribute.id}]`" x-model="filters[attribute.id]" class="w-full rounded-xl border-0 bg-slate-50 text-sm font-normal ring-1 ring-slate-100 focus:ring-primary">
                                                <option value="">همه</option>
                                                <template x-for="option in attribute.options" :key="option"><option :value="option" x-text="option"></option></template>
                                            </select>
                                        </template>
                                        <template x-if="attribute.type === 'multi_select'">
                                            <select multiple :name="`filters[${attribute.id}][]`" x-model="filters[attribute.id]" x-init="filters[attribute.id] = Array.isArray(filters[attribute.id]) ? filters[attribute.id] : (filters[attribute.id] ? [filters[attribute.id]] : [])" class="min-h-28 w-full rounded-xl border-0 bg-slate-50 text-sm font-normal ring-1 ring-slate-100 focus:ring-primary">
                                                <template x-for="option in attribute.options" :key="option"><option :value="option" x-text="option"></option></template>
                                            </select>
                                            <p class="mt-1 text-[10px] font-normal text-slate-400">برای چند انتخاب، کلید Ctrl یا لمس چندگانه را استفاده کنید.</p>
                                        </template>
                                        <template x-if="attribute.type === 'integer' || attribute.type === 'decimal'">
                                            <input :name="`filters[${attribute.id}]`" x-model="filters[attribute.id]" :type="attribute.type === 'integer' ? 'number' : 'number'" :step="attribute.type === 'decimal' ? '0.01' : '1'" class="w-full rounded-xl border-0 bg-slate-50 text-sm font-normal ring-1 ring-slate-100 focus:ring-primary" />
                                        </template>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button class="mobile-button flex-1 bg-neutral text-white hover:bg-neutral-800">اعمال فیلتر</button>
                            <button type="button" @click="reset" class="rounded-xl px-3 text-xs font-bold text-slate-500 hover:bg-slate-50">پاک کردن</button>
                        </div>
                    </form>
                </div>
            </aside>

            <div class="min-w-0" @click="paginateResults($event)">
                <div id="listing-results" :aria-busy="resultsLoading.toString()" aria-live="polite" aria-atomic="false">
                    @include('listings.partials.results', ['listings' => $listings, 'sort' => $sort, 'activeFilters' => $activeFilters])
                </div>
                <div x-show="resultsLoading" x-cloak class="pointer-events-none fixed inset-x-4 top-20 z-40 mx-auto flex max-w-md items-center justify-center gap-2 rounded-2xl bg-neutral px-4 py-3 text-xs font-bold text-white shadow-xl" role="status" aria-live="polite"><span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>در حال به‌روزرسانی نتایج...</div>
            </div>
        </div>
    </section>
</x-app-layout>
