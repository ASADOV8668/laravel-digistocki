<x-app-layout title="خانه">
    <section x-data="pullToRefresh()" @touchstart.passive="touchStart($event)" @touchmove.passive="touchMove($event)"
        @touchend="touchEnd()" class="relative space-y-7 px-4 py-6">
        <div x-cloak x-show="distance > 0 || refreshing"
            class="pointer-events-none fixed inset-x-0 top-2 z-40 mx-auto flex max-w-md justify-center"
            aria-live="polite"><span
                class="rounded-full bg-white px-4 py-2 text-xs font-bold text-primary shadow-lg ring-1 ring-primary/10"
                x-text="refreshing ? 'در حال تازه‌سازی...' : 'برای تازه‌سازی رها کنید'"></span></div>

        <div x-data="homeSearch(@js(url('/locations/provinces')), @js(route('listings.search.suggestions')), @js(url('/listings/models')))" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <p class="text-xs font-bold text-primary">جستجوی نزدیک‌تر</p>
                <h2 class="mt-1 text-lg font-black text-neutral">دنبال چه چیزی هستید؟</h2>
                <p class="mt-1 text-xs leading-6 text-slate-400">مدل گوشی را جستجو کنید و محدوده موردنظر را انتخاب کنید.
                </p>
            </div>
            <form method="GET" action="{{ route('listings.index') }}" class="space-y-3">
                <input type="hidden" name="province_id" x-model="provinceId"><input type="hidden" name="city_id"
                    x-model="cityId"><input type="hidden" name="q" x-model="searchQuery">
                <template x-for="cityIdValue in cityIds" :key="'home-hidden-city-' + cityIdValue"><input type="hidden"
                        name="city_ids[]" :value="cityIdValue"></template>
                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-2 rounded-2xl bg-slate-100 p-2">
                    <div class="relative" @click.outside="open = false"><label for="home-search"
                            class="sr-only">جستجو</label><x-heroicon-o-magnifying-glass
                            class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" /><input
                            id="home-search" x-model="query" @input="search"
                            @focus="query.length >= 2 && (open = true)" @keydown.escape="open = false" type="search"
                            autocomplete="off" placeholder="جستجو" :readonly="Boolean(selectedModelId)"
                            class="public-input bg-white pr-10 pl-10"><button x-show="selectedModelId" x-cloak
                            type="button" @click="resetModelSelection" aria-label="پاک کردن انتخاب مدل"
                            class="absolute left-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-primary"><x-heroicon-o-x-mark
                                class="h-4 w-4" /></button><input
                            type="hidden" name="brand_id" x-model="selectedBrandId"><input type="hidden"
                            name="phone_model_id" x-model="selectedModelId">
                        <div x-show="open && !selectedModelId" x-cloak
                            class="absolute inset-x-0 top-full z-30 mt-2 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                            <div x-show="loading" class="px-3 py-3 text-xs text-slate-500">در حال جستجو...</div>
                            <template x-if="!loading && suggestions.brands.length">
                                <div>
                                    <p class="px-3 pb-1 pt-2 text-[11px] font-bold text-slate-400">برندها</p><template
                                        x-for="brand in suggestions.brands" :key="'home-brand-' + brand.id"><button
                                            type="button" @click="selectBrand(brand)"
                                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-right text-sm hover:bg-primary-50"><span
                                                x-text="brand.label" class="font-bold text-neutral"></span><span
                                                x-text="brand.secondary"
                                                class="text-xs text-slate-400"></span></button></template>
                                </div>
                            </template><template x-if="!loading && suggestions.models.length">
                                <div>
                                    <p class="px-3 pb-1 pt-2 text-[11px] font-bold text-slate-400">مدل‌ها</p><template
                                        x-for="model in suggestions.models" :key="'home-model-' + model.id"><button
                                            type="button" @click="selectModel(model)"
                                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-right text-sm hover:bg-primary-50"><span><span
                                                    x-text="model.label" class="font-bold text-neutral"></span><span
                                                    x-text="model.brand ? ' · ' + model.brand : ''"
                                                    class="mr-1 text-xs text-slate-400"></span></span><span
                                                x-text="model.secondary"
                                                class="text-xs text-slate-400"></span></button></template>
                                </div>
                            </template>
                            <div x-show="!loading && !suggestions.brands.length && !suggestions.models.length"
                                class="px-3 py-3 text-xs text-slate-500">موردی پیدا نشد.</div>
                        </div>
                        <div x-show="open && selectedModelId" x-cloak
                            class="absolute inset-x-0 top-full z-30 mt-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                            <div x-show="attributesLoading" class="px-2 py-2 text-xs text-slate-500">در حال بارگذاری
                                ویژگی‌های موجود...</div>
                            <template x-if="!attributesLoading && quickStep === 'storage' && quickAttribute('storage')">
                                <div>
                                    <p class="px-2 pb-2 text-[11px] font-black text-slate-400">حافظه داخلی را انتخاب کنید</p>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="selectQuickAttribute(quickAttribute('storage'), '')"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-neutral hover:border-primary hover:bg-primary-50">همه
                                            موارد</button>
                                        <template x-for="option in quickOptions('storage')" :key="`home-storage-${option}`">
                                            <button type="button" @click="selectQuickAttribute(quickAttribute('storage'), option)"
                                                class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-neutral hover:border-primary hover:bg-primary-50">
                                                <span x-text="option"></span> <span class="text-[10px] text-slate-400">GB</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!attributesLoading && quickStep === 'color' && quickAttribute('color')">
                                <div>
                                    <p class="px-2 pb-2 text-[11px] font-black text-slate-400">حالا رنگ را انتخاب کنید</p>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="selectQuickAttribute(quickAttribute('color'), '')"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-neutral hover:border-primary hover:bg-primary-50">همه
                                            موارد</button>
                                        <template x-for="option in quickOptions('color')" :key="`home-color-${option}`">
                                            <button type="button" @click="selectQuickAttribute(quickAttribute('color'), option)"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-neutral hover:border-primary hover:bg-primary-50">
                                                <span class="h-3.5 w-3.5 rounded-full border border-slate-300"
                                                    :style="`background-color: ${window.listingColorHex?.[option] || '#94a3b8'}`"></span>
                                                <span x-text="option"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <button x-show="quickStep === 'done'" type="button" @click="quickStep = 'storage'; open = true"
                                class="w-full rounded-lg bg-primary-50 px-3 py-2 text-xs font-bold text-primary">تغییر حافظه یا رنگ</button>
                        </div>
                    </div>
                    <button type="button" @click="openLocationPicker" :disabled="locationLoading"
                        class="flex min-w-28 items-center justify-center gap-1.5 rounded-xl bg-white px-3 py-3 text-xs font-black text-neutral shadow-sm transition hover:bg-primary-50 hover:text-primary disabled:opacity-60"><x-heroicon-o-map-pin
                            class="h-5 w-5 text-primary" /><span class="max-w-28 truncate"
                            x-text="locationLoading ? 'در حال یافتن...' : locationLabel">همه ایران</span></button>
                </div>
                <template x-for="attribute in quickAttributes()" :key="`home-hidden-attribute-${attribute.id}`">
                    <input type="hidden" :name="`filters[${attribute.id}]`" x-model="filters[attribute.id]">
                </template>
                <button
                    class="mobile-button w-full bg-primary text-white hover:bg-primary-600 focus:ring-4 focus:ring-primary/30 focus:ring-offset-2"><x-heroicon-o-magnifying-glass
                        class="h-5 w-5" />جستجوی آگهی‌ها</button>
            </form>

            <div x-show="locationModalOpen" x-cloak x-transition.opacity
                class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/45 p-3 sm:items-center">
                <div x-show="locationModalOpen" x-transition @click.outside="confirmLocation" role="dialog"
                    aria-modal="true" aria-labelledby="home-location-title"
                    class="max-h-[88vh] w-full max-w-md overflow-y-auto rounded-lg bg-white p-5 shadow-2xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-primary">محدوده جستجو</p>
                            <h3 id="home-location-title" class="mt-1 text-lg font-black text-neutral">کجا جستجو کنیم؟
                            </h3>
                        </div><button type="button" @click="confirmLocation" aria-label="بستن"
                            class="rounded-xl p-2 text-slate-500 hover:bg-slate-100"><x-heroicon-o-x-mark
                                class="h-5 w-5" /></button>
                    </div>
                    <button type="button" @click="useLocation" :disabled="locationLoading"
                        class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl border border-primary/20 bg-primary-50 px-4 py-3 text-sm font-black text-primary disabled:opacity-60"><x-heroicon-o-map-pin
                            class="h-5 w-5" /><span
                            x-text="locationLoading ? 'در حال دریافت موقعیت شما...' : 'استفاده از موقعیت فعلی'">استفاده
                            از موقعیت فعلی</span></button>
                    <p x-show="locationLoading" class="mt-3 text-center text-xs text-slate-400">اگر GPS موقعیت را پیدا
                        نکند، انتخاب دستی در همین پنجره فعال است.</p>
                    <div x-show="locationError" x-cloak
                        class="mt-3 rounded-xl bg-error/10 px-3 py-2 text-xs font-bold leading-6 text-error"
                        x-text="locationError"></div>
                    <div class="mt-5 border-t border-slate-100 pt-5">
                        <p class="mb-3 text-sm font-black text-neutral">یا محدوده را دستی انتخاب کنید</p><label
                            for="home-province"
                            class="mb-1.5 block text-xs font-bold text-slate-600">استان</label><select
                            id="home-province" x-model="provinceId" @change="loadCities" class="public-select"
                            data-flowbite-focus="focus:ring-2 focus:ring-primary/30">
                            <option value="">همه استان‌ها</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}">{{ $province->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="provinceId" x-cloak class="mt-4">
                        <p class="mb-2 text-xs font-bold text-slate-600">شهرها</p>
                        <div
                            class="grid max-h-44 grid-cols-2 gap-2 overflow-y-auto rounded-xl bg-slate-50 p-2 sm:grid-cols-3">
                            <template x-for="city in cities" :key="'home-modal-city-' + city.id"><label
                                    class="flex cursor-pointer items-center gap-2 rounded-lg bg-white px-2 py-2 text-xs font-bold text-slate-600"><input
                                        type="checkbox" :value="city.id"
                                        :checked="cityIds.includes(String(city.id))" @change="toggleCity(city.id)"
                                        class="public-check"><span x-text="city.name"></span></label></template></div>
                        <p x-show="citiesLoading" class="mt-2 text-[11px] text-slate-400">در حال دریافت شهرها...</p>
                        <p x-show="!citiesLoading && !cities.length" class="mt-2 text-[11px] text-slate-400">شهری برای
                            این استان پیدا نشد.</p>
                    </div>
                    <div class="mt-5 flex gap-2"><button type="button" @click="clearLocation"
                            class="flex-1 rounded-xl bg-slate-100 px-4 py-3 text-xs font-black text-slate-600">همه
                            ایران</button><button type="button" @click="confirmLocation"
                            class="flex-1 rounded-xl bg-primary px-4 py-3 text-xs font-black text-white">اعمال
                            محدوده</button></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2 rounded-lg border border-slate-100 bg-white p-3 shadow-sm">
            <div class="flex flex-col items-center gap-1 text-center"><span
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-success/10 text-success"><x-heroicon-o-check-badge
                        class="h-5 w-5" /></span><span class="text-[10px] font-bold text-slate-600">آگهی
                    بررسی‌شده</span></div>
            <div class="flex flex-col items-center gap-1 border-x border-slate-100 text-center"><span
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-info/10 text-info"><x-heroicon-o-sparkles
                        class="h-5 w-5" /></span><span class="text-[10px] font-bold text-slate-600">جستجوی
                    هوشمند</span></div>
            <div class="flex flex-col items-center gap-1 text-center"><span
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"><x-heroicon-o-lock-closed
                        class="h-5 w-5" /></span><span class="text-[10px] font-bold text-slate-600">خرید امن‌تر</span>
            </div>
        </div>
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-neutral">دسته‌های محبوب</h2>
                    <p class="mt-1 text-xs text-slate-400">سریع‌تر به مدل موردنظرت برس</p>
                </div><a href="{{ route('listings.index') }}" class="text-xs font-bold text-primary">مشاهده همه</a>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @foreach ([['آیفون', 'bg-slate-900'], ['سامسونگ', 'bg-blue-600'], ['شیائومی', 'bg-orange-500']] as [$brand, $color])
                    <a href="{{ route('listings.index', ['q' => $brand]) }}"
                        class="group rounded-lg border border-slate-200 bg-white p-3 text-center shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                        <div
                            class="mx-auto mb-2 flex h-11 w-11 items-center justify-center rounded-lg {{ $color }} text-white transition group-hover:scale-105">
                            <x-heroicon-o-device-phone-mobile class="h-6 w-6" /></div><span
                            class="text-xs font-black text-neutral">{{ $brand }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-neutral">آخرین آگهی‌ها</h2>
                    <p class="mt-1 text-xs text-slate-400">تازه‌ترین فرصت‌های خرید موبایل</p>
                </div><a href="{{ route('listings.index') }}" class="text-xs font-bold text-primary">مشاهده همه</a>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @forelse ($latestListings as $listing)
                <x-listing-card :listing="$listing" />@empty<div
                        class="col-span-2 rounded-lg bg-white p-6 text-center text-sm text-slate-500">هنوز آگهی
                        تأییدشده‌ای ثبت نشده است.</div>
                @endforelse
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-secondary/10 p-3 text-secondary"><x-heroicon-o-shield-check
                        class="h-6 w-6" /></div>
                <div>
                    <h3 class="font-bold text-neutral">خرید و فروش امن‌تر</h3>
                    <p class="mt-1 text-xs leading-6 text-slate-500">آگهی‌ها پیش از انتشار توسط تیم ما بررسی می‌شوند تا
                        تجربه مطمئن‌تری داشته باشید.</p>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
