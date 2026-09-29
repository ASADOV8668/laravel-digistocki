@props(['listing'])
@php($image = $listing->primaryImage ?? $listing->images->first())
<article class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-900/5">
    <a href="{{ route('listings.show', $listing) }}" class="block">
        <div class="relative overflow-hidden bg-slate-100">
            @if ($image)<img src="{{ asset('storage/'.($image->thumbnail_path ?: $image->path)) }}" alt="{{ $listing->title }}" class="h-40 w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">@else<div class="flex h-40 items-center justify-center bg-primary-50 text-primary"><x-heroicon-o-device-phone-mobile class="h-14 w-14 transition group-hover:scale-110" /></div>@endif
            <span class="absolute right-2 top-2 rounded-full bg-white/90 px-2 py-1 text-[10px] font-bold text-slate-600 shadow-sm">تأییدشده</span>
        </div>
        <div class="p-3.5"><div class="flex items-start justify-between gap-2"><h3 class="line-clamp-2 min-h-10 text-sm font-black leading-5 text-neutral">{{ $listing->title }}</h3><x-heroicon-o-heart class="h-5 w-5 shrink-0 text-slate-300 transition group-hover:text-primary" /></div><p class="mt-2 truncate text-[11px] text-slate-500">{{ $listing->brand->name }} · {{ $listing->phoneModel->name }}</p><div class="mt-3 flex items-end justify-between gap-2"><p class="text-sm font-black text-primary">{{ number_format($listing->price) }} <span class="text-[10px] font-bold">تومان</span></p><span class="text-[10px] font-medium text-slate-400">{{ $listing->created_at?->diffForHumans() }}</span></div></div>
    </a>
</article>
