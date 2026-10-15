@if (count($activeFilters))
    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
        <span class="text-[11px] font-black text-slate-500">فیلترهای فعال:</span>
        @foreach ($activeFilters as $filter)
            <a href="{{ $filter['url'] }}"
                class="listing-filter-chip inline-flex items-center gap-1 rounded-full bg-primary-50 px-3 py-1.5 text-[11px] font-bold text-primary transition hover:bg-primary-100">
                <span>{{ $filter['label'] }}</span>
                <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
            </a>
        @endforeach
        <a href="{{ route('storefront.show', $store) }}"
            class="listing-filter-chip mr-auto text-[11px] font-bold text-slate-500 hover:text-error">پاک کردن همه</a>
    </div>
@endif

<div class="mb-4 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
    <div>
        <h2 class="text-sm font-black text-neutral">آگهی‌های این فروشگاه</h2>
        <p class="mt-1 text-[11px] text-slate-400">مرتب‌سازی بر اساس
            {{ ['newest' => 'جدیدترین', 'price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین', 'views' => 'پربازدیدترین'][$sort] }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-black text-slate-600">
            {{ number_format($listings->total()) }} نتیجه
        </span>
        <x-listing-view-switcher />
    </div>
</div>

@if ($listings->count())
    @php($listingView = in_array(request()->input('view', request()->cookie('listing_view', 'list')), ['list', 'grid'], true) ? request()->input('view', request()->cookie('listing_view', 'list')) : 'list')
    <div data-listing-feed data-pagination-mode="{{ $paginationMode }}"
        data-next-page-url="{{ $listings->nextPageUrl() ?? '' }}">
        <div data-listing-items class="{{ $listingView === 'list' ? 'space-y-3' : 'grid grid-cols-2 gap-4 lg:grid-cols-2' }}">
            @include('listings.partials.cards', ['listings' => $listings])
        </div>

        @if ($paginationMode === 'pagination')
            <div class="listing-pagination mt-5">
                {{ $listings->links('vendor.pagination.flowbite') }}
            </div>
        @else
            <div data-infinite-skeleton hidden class="{{ $listingView === 'list' ? 'space-y-3' : 'mt-4 grid grid-cols-2 gap-4' }}" aria-live="polite">
                @for ($index = 0; $index < 4; $index++)
                    <div class="animate-pulse overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <div class="h-48 bg-slate-200"></div>
                        <div class="space-y-3 p-4">
                            <div class="h-4 rounded bg-slate-200"></div>
                            <div class="h-3 w-2/3 rounded bg-slate-200"></div>
                            <div class="h-4 w-1/2 rounded bg-slate-200"></div>
                        </div>
                    </div>
                @endfor
            </div>
            <div data-infinite-sentinel class="h-4" aria-hidden="true"></div>
        @endif
    </div>
@else
    <div class="rounded-lg border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-primary-50 text-primary">
            <x-heroicon-o-magnifying-glass class="h-7 w-7" />
        </span>
        <h3 class="mt-4 font-black text-neutral">نتیجه‌ای پیدا نشد</h3>
        <p class="mt-2 text-xs leading-6 text-slate-500">فیلترها را تغییر دهید یا عبارت جستجو را ساده‌تر کنید.</p>
        <a href="{{ route('storefront.show', $store) }}"
            class="mt-4 inline-flex rounded-lg bg-primary px-5 py-2.5 text-xs font-bold text-white transition hover:bg-primary-600">پاک
            کردن فیلترها</a>
    </div>
@endif
