<x-app-layout title="علاقه‌مندی‌ها">
    <x-slot name="header"><div class="flex items-center justify-between"><h1 class="text-xl font-black text-neutral">علاقه‌مندی‌های من</h1><span class="text-xs text-slate-500">{{ $favorites->total() }} آگهی</span></div></x-slot>
    <section class="space-y-5 px-4 py-6">
        @if (session('status'))<div class="rounded-xl bg-secondary/10 p-3 text-sm font-bold text-success">{{ session('status') }}</div>@endif
        @if ($favorites->count())
            <div class="grid grid-cols-2 gap-3">@foreach ($favorites as $favorite)<x-listing-card :listing="$favorite->listing" />@endforeach</div>
            <div>{{ $favorites->links() }}</div>
        @else
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm"><x-heroicon-o-heart class="mx-auto h-12 w-12 text-primary/30" /><h2 class="mt-4 font-black text-neutral">هنوز آگهی‌ای ذخیره نکرده‌اید</h2><p class="mt-2 text-sm leading-6 text-slate-500">در صفحه هر آگهی روی قلب بزنید تا بعداً سریع به آن برگردید.</p><a href="{{ route('listings.index') }}" class="mt-5 inline-flex rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white">مشاهده آگهی‌ها</a></div>
        @endif
    </section>
</x-app-layout>
