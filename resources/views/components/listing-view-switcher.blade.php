@props(['view' => null])
@php
    $activeView = in_array($view ?: request()->input('view', request()->cookie('listing_view', 'list')), ['list', 'grid'], true)
        ? ($view ?: request()->input('view', request()->cookie('listing_view', 'list')))
        : 'list';
    $query = request()->query();
    unset($query['page'], $query['view'], $query['infinite']);
    $urlFor = function (string $targetView) use ($query): string {
        return request()->url().'?'.http_build_query($query + ['view' => $targetView], '', '&', PHP_QUERY_RFC3986);
    };
@endphp
<div class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1"
    role="group" aria-label="نحوه نمایش آگهی‌ها">
    <a href="{{ $urlFor('list') }}" data-listing-view-link data-view="list"
        aria-label="نمای فهرستی" @if ($activeView === 'list') aria-current="page" @endif
        class="inline-flex h-8 w-8 items-center justify-center rounded-md transition {{ $activeView === 'list' ? 'bg-white text-primary shadow-sm' : 'text-slate-400 hover:bg-white hover:text-primary' }}">
        <x-heroicon-o-list-bullet class="h-4 w-4" />
        <span class="sr-only">نمای فهرستی</span>
    </a>
    <a href="{{ $urlFor('grid') }}" data-listing-view-link data-view="grid"
        aria-label="نمای شبکه‌ای" @if ($activeView === 'grid') aria-current="page" @endif
        class="inline-flex h-8 w-8 items-center justify-center rounded-md transition {{ $activeView === 'grid' ? 'bg-white text-primary shadow-sm' : 'text-slate-400 hover:bg-white hover:text-primary' }}">
        <x-heroicon-o-squares-2x2 class="h-4 w-4" />
        <span class="sr-only">نمای شبکه‌ای</span>
    </a>
</div>
