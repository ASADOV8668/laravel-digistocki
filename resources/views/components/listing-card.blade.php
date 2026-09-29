@props(['listing'])
<article class="overflow-hidden rounded-2xl bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
    <a href="{{ route('listings.show', $listing) }}" class="block">
        @php($image = $listing->primaryImage ?? $listing->images->first())
        @if ($image)
            <img src="{{ asset('storage/'.($image->thumbnail_path ?: $image->path)) }}" alt="{{ $listing->title }}" class="h-36 w-full object-cover" loading="lazy">
        @else
            <div class="flex h-36 items-center justify-center bg-primary-50 text-primary"><x-heroicon-o-device-phone-mobile class="h-14 w-14" /></div>
        @endif
        <div class="p-4"><div class="flex items-start justify-between gap-2"><h3 class="line-clamp-2 text-sm font-bold text-neutral">{{ $listing->title }}</h3><x-heroicon-o-heart class="h-5 w-5 shrink-0 text-slate-300" /></div><p class="mt-2 text-xs text-slate-500">{{ $listing->brand->name }} · {{ $listing->phoneModel->name }}</p><p class="mt-3 text-sm font-black text-primary">{{ number_format($listing->price) }} تومان</p></div>
    </a>
</article>
