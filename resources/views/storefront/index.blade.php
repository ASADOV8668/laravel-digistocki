<x-app-layout title="فروشگاه‌ها">
    <x-slot name="header"><div class="flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary"><x-heroicon-o-building-storefront class="h-6 w-6" /></span><div><p class="text-xs font-bold text-primary">فروشندگان دیجی استوکی</p><h1 class="mt-1 text-xl font-black text-neutral">فروشگاه‌ها</h1></div></div></x-slot>
    <section x-data="storefrontDirectory($el.dataset.citiesEndpoint, $el.dataset.provinceId, $el.dataset.cityIds ? $el.dataset.cityIds.split(',') : [])" data-cities-endpoint="{{ url('/locations/provinces') }}" data-province-id="{{ $selectedProvince }}" data-city-ids="{{ implode(',', $selectedCityIds) }}" class="space-y-5 px-4 py-6">
        <form method="GET" action="{{ route('storefront.index') }}" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2"><div><label for="stores-province" class="mb-2 block text-sm font-bold text-neutral">استان</label><select id="stores-province" name="province_id" x-model="provinceId" @change="loadCities()" class="public-select"><option value="">همه استان‌ها</option>@foreach ($provinces as $province)<option value="{{ $province->id }}">{{ $province->name }}</option>@endforeach</select></div><div><label for="stores-city" class="mb-2 block text-sm font-bold text-neutral">شهرها</label><select id="stores-city" name="city_ids[]" x-model="cityIds" multiple :disabled="!provinceId || citiesLoading" class="public-select min-h-32"><template x-for="city in cities" :key="city.id"><option :value="city.id" x-text="city.name"></option></template></select><p class="mt-1 text-[11px] text-slate-400">برای انتخاب چند شهر، کلید Ctrl یا لمس چندگانه را استفاده کنید.</p></div></div>
            <button class="mobile-button mt-4 w-full bg-primary text-white hover:bg-primary-600 sm:w-auto"><x-heroicon-o-funnel class="h-5 w-5" />نمایش فروشگاه‌ها</button>
        </form>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($stores as $store)
                <a href="{{ route('storefront.show', $store) }}" class="group rounded-lg border border-slate-200 bg-white p-4 text-center shadow-sm transition hover:-translate-y-1 hover:border-primary/30 hover:shadow-md">
                    @if ($store->logo_path)<img src="{{ asset('storage/'.$store->logo_path) }}" alt="{{ $store->name }}" class="mx-auto h-20 w-20 rounded-full object-cover ring-4 ring-primary-50">@else<span class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary-50 text-2xl font-black text-primary ring-4 ring-primary-50">{{ mb_substr($store->name, 0, 1) }}</span>@endif
                    <h2 class="mt-4 truncate font-black text-neutral group-hover:text-primary">{{ $store->name }}</h2><p class="mt-1 truncate text-xs text-slate-400">{{ collect([$store->province?->name, $store->city?->name])->filter()->implode('، ') ?: 'موقعیت ثبت نشده' }}</p>
                </a>
            @empty
                <div class="col-span-full rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">فروشگاه فعالی در این محدوده پیدا نشد.</div>
            @endforelse
        </div>
        {{ $stores->links() }}
    </section>
</x-app-layout>
