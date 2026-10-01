@php
    $siteTitle = app(\App\Services\SystemOptions::class)->get('site_title');
    $storeSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $store->name,
        'url' => route('storefront.show', $store),
    ];
    if ($store->logo_path) {
        $storeSchema['image'] = asset('storage/'.$store->logo_path);
        $storeSchema['logo'] = asset('storage/'.$store->logo_path);
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
@endphp
@push('head')
    <meta name="description" content="{{ $store->name }}؛ مشاهده اطلاعات فروشگاه و آخرین آگهی‌های فعال فروشنده در {{ $siteTitle }}.">
    <script type="application/ld+json">{!! json_encode($storeSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
<x-app-layout :title="$store->name">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('listings.index') }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" aria-label="بازگشت">
                <x-heroicon-o-arrow-right class="h-5 w-5" />
            </a>
            <div>
                <p class="text-[11px] font-bold text-primary">غرفه فروشنده</p>
                <h1 class="mt-1 text-xl font-black text-neutral">{{ $store->name }}</h1>
            </div>
        </div>
    </x-slot>

    <section class="space-y-6 px-4 py-6">
        <div class="overflow-hidden rounded-lg bg-neutral p-5 text-white shadow-xl shadow-slate-900/10">
            <div class="flex items-start gap-4">
                @if ($store->logo_path)
                    <img src="{{ asset('storage/'.$store->logo_path) }}" alt="{{ $store->name }}" class="h-20 w-20 rounded-lg bg-white object-cover p-1">
                @else
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-primary text-3xl font-black">{{ mb_substr($store->name, 0, 1) }}</div>
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
            @if ($store->address || $store->contact_phone)
                <div class="mt-5 grid gap-2 border-t border-white/10 pt-4 text-xs text-white/70 sm:grid-cols-2">
                    @if ($store->address)
                        <p class="flex items-start gap-2"><x-heroicon-o-building-office-2 class="mt-0.5 h-4 w-4 shrink-0" />{{ $store->address }}</p>
                    @endif
                    @if ($store->contact_phone)
                        <a href="tel:{{ $store->contact_phone }}" class="flex items-center gap-2 text-white transition hover:text-primary-100"><x-heroicon-o-phone class="h-4 w-4" />{{ $store->contact_phone }}</a>
                    @endif
                </div>
            @endif
            <div x-data="shareLink(@js(route("storefront.show", $store)))" class="mt-5 flex flex-wrap gap-2 border-t border-white/10 pt-4">
                <button type="button" @click="share()" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-xs font-black text-white transition hover:bg-primary-600"><x-heroicon-o-share class="h-4 w-4" />اشتراک‌گذاری غرفه</button>
                <button type="button" @click="copy()" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-white/15"><x-heroicon-o-clipboard-document class="h-4 w-4" /><span x-text="copied ? 'لینک کپی شد' : 'کپی لینک'">کپی لینک</span></button>
            </div>
        </div>

        <div>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold text-primary">ویترین فروشنده</p>
                    <h2 class="mt-1 text-lg font-black text-neutral">آخرین آگهی‌ها</h2>
                </div>
                <span class="rounded-full bg-primary/10 px-3 py-1.5 text-[11px] font-bold text-primary">{{ number_format($listings->total()) }} آگهی</span>
            </div>
            <form method="GET" class="mb-4 flex gap-2">
                <label for="storefront-search" class="sr-only">جستجو در آگهی‌های غرفه</label>
                <input id="storefront-search" name="q" value="{{ request('q') }}" placeholder="جستجو در آگهی‌های این غرفه" class="public-input min-w-0 flex-1 bg-white">
                <button class="rounded-lg bg-neutral px-4 py-3 text-xs font-black text-white"><x-heroicon-o-magnifying-glass class="h-4 w-4" /></button>
            </form>
            @if ($listings->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    @foreach ($listings as $listing)
                        <x-listing-card :listing="$listing" />
                    @endforeach
                </div>
                <div class="pt-2">{{ $listings->links('vendor.pagination.flowbite') }}</div>
            @else
                <div class="rounded-lg bg-white p-8 text-center shadow-sm border border-slate-200">
                    <x-heroicon-o-device-phone-mobile class="mx-auto h-12 w-12 text-slate-300" />
                    <p class="mt-3 text-sm font-bold text-slate-500">این غرفه هنوز آگهی فعالی ندارد.</p>
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
