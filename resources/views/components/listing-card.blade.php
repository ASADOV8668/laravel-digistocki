@props(['listing'])
@php
    $image = $listing->primaryImage ?? $listing->images->first();
    $isFavorited = filter_var($listing->is_favorited ?? false, FILTER_VALIDATE_BOOLEAN);
    $allCardAttributes = collect($listing->attributeValues ?? [])->map(function ($value) {
        $raw = $value->value_json ?? $value->value_string ?? $value->value_integer ?? $value->value_decimal;
        if ($raw === null && $value->value_boolean !== null) {
            $raw = $value->value_boolean ? 'دارد' : 'ندارد';
        }
        if (is_array($raw)) {
            $raw = implode('، ', $raw);
        }
        return [
            'name' => $value->attribute?->name,
            'slug' => $value->attribute?->slug,
            'value' => $raw,
            'color' => $value->attribute?->slug === 'color' ? \App\Support\ColorPalette::hex((string) $raw) : null,
        ];
    })->filter(fn ($item) => filled($item['name']) && filled($item['value']));
    $colorAttribute = $allCardAttributes->firstWhere('slug', 'color');
    $cardAttributes = ($colorAttribute ? collect([$colorAttribute]) : collect())
        ->concat($allCardAttributes->reject(fn ($item) => $item['slug'] === 'color'))
        ->take(2);
@endphp
<article x-data="favoriteToggle('{{ route('listings.favorite.toggle', $listing) }}', {{ $isFavorited ? 'true' : 'false' }}, {{ auth()->check() ? 'true' : 'false' }})" class="group overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-900/5">
    <div class="relative overflow-hidden bg-slate-100">
        <a href="{{ route('listings.show', $listing) }}" class="block">
            <img src="{{ $image ? asset('storage/'.($image->thumbnail_path ?: $image->path)) : asset('images/listing-placeholder.svg') }}" alt="{{ $image ? $listing->title : 'تصویر پیش‌فرض آگهی موبایل' }}" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
        </a>
        <button type="button" @click.stop.prevent="toggle" :aria-pressed="favorited.toString()" :aria-label="favorited ? 'حذف آگهی از علاقه‌مندی‌ها' : 'افزودن آگهی به علاقه‌مندی‌ها'" class="absolute left-2 top-2 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white/95 text-slate-300 shadow-sm transition hover:text-error focus:outline-none focus:ring-4 focus:ring-primary/20" :class="favorited ? 'text-error' : 'text-slate-300'"><x-heroicon-o-heart x-show="!favorited" class="h-5 w-5" /><x-heroicon-s-heart x-show="favorited" x-cloak class="h-5 w-5 fill-current" /></button>
        <span class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-neutral/90 px-2.5 py-1.5 text-[10px] font-black text-white shadow-sm" aria-label="{{ \App\Support\PersianNumber::digits($listing->images->count()) }} تصویر"><x-heroicon-o-camera class="h-3.5 w-3.5" /><span>{{ \App\Support\PersianNumber::digits($listing->images->count()) }}</span></span>
    </div>
    <a href="{{ route('listings.show', $listing) }}" class="block p-3.5">
        <h3 class="line-clamp-2 min-h-10 text-sm font-black leading-5 text-neutral">{{ $listing->title }}</h3>
        <p class="mt-2 truncate text-[11px] text-slate-500">{{ $listing->brand->name }} · {{ $listing->phoneModel->name_fa ?: $listing->phoneModel->name }}</p>
        @if ($cardAttributes->isNotEmpty())
            <div class="mt-2 space-y-1.5 text-[11px] text-slate-500">@foreach ($cardAttributes as $attribute)<p class="flex items-center gap-1.5 truncate"><span class="h-3 w-3 shrink-0 rounded-full border border-slate-300" @if($attribute['color']) style="background-color: {{ $attribute['color'] }}" @else style="background-color: rgb(148 163 184 / .8)" @endif></span><span class="font-bold">{{ $attribute['name'] }}:</span><span class="truncate">{{ $attribute['value'] }}</span></p>@endforeach</div>
        @endif
        <div class="mt-3 border-t border-slate-100 pt-3"><div class="flex items-center justify-between gap-2 text-[10px] font-medium text-slate-400"><span class="inline-flex min-w-0 items-center gap-1 truncate"><x-heroicon-o-map-pin class="h-3.5 w-3.5 shrink-0" />{{ $listing->province?->name ?: 'استان ثبت نشده' }}{{ $listing->city?->name ? '، '.$listing->city->name : '' }}</span><span class="shrink-0">{{ \App\Support\PersianDate::human($listing->published_at ?? $listing->created_at) }}</span></div><p class="mt-2 text-sm font-black text-primary">{{ $listing->price_on_request ? 'تماس بگیرید' : \App\Support\PersianNumber::format($listing->price).' تومان' }}</p></div>
    </a>
    @if ($listing->user?->storefront?->isPubliclyEnabled())
        <div class="border-t border-slate-100 px-3.5 py-2.5">
            <a href="{{ route('storefront.show', $listing->user->storefront) }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-primary transition hover:text-primary-600">
                <x-heroicon-o-building-storefront class="h-4 w-4" /> غرفه فروشنده
            </a>
        </div>
    @endif
</article>
