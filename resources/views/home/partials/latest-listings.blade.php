<div data-listing-feed data-pagination-mode="{{ $paginationMode }}"
    data-next-page-url="{{ $latestListings->nextPageUrl() ?? '' }}">
    @if ($latestListings->count())
        @php($listingView = in_array(request()->input('view', request()->cookie('listing_view', 'list')), ['list', 'grid'], true) ? request()->input('view', request()->cookie('listing_view', 'list')) : 'list')
        <div data-listing-items class="{{ $listingView === 'list' ? 'space-y-3' : 'grid grid-cols-2 gap-3' }}">
            @include('home.partials.latest-cards', ['latestListings' => $latestListings])
        </div>

        @if ($paginationMode === 'pagination')
            <div class="listing-pagination mt-5">
                {{ $latestListings->links('vendor.pagination.flowbite') }}
            </div>
        @else
            <div data-infinite-skeleton hidden class="hidden {{ $listingView === 'list' ? 'space-y-3' : 'mt-4 grid grid-cols-2 gap-3' }}" aria-live="polite">
                @for ($index = 0; $index < 2; $index++)
                    <div class="animate-pulse overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <div class="h-44 bg-slate-200"></div>
                        <div class="space-y-3 p-3">
                            <div class="h-4 rounded bg-slate-200"></div>
                            <div class="h-3 w-2/3 rounded bg-slate-200"></div>
                            <div class="h-4 w-1/2 rounded bg-slate-200"></div>
                        </div>
                    </div>
                @endfor
            </div>
            <div data-infinite-sentinel class="h-4" aria-hidden="true"></div>
        @endif
    @else
        <div class="col-span-2 rounded-lg bg-white p-6 text-center text-sm text-slate-500">
            هنوز آگهی تأییدشده‌ای ثبت نشده است.
        </div>
    @endif
</div>
