<x-app-layout title="غرفه غیرفعال">
    @push('head')
        <meta name="robots" content="noindex,nofollow">
    @endpush
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" aria-label="بازگشت">
                <x-heroicon-o-arrow-right class="h-5 w-5" />
            </a>
            <div>
                <p class="text-[11px] font-bold text-primary">غرفه فروشنده</p>
                <h1 class="mt-1 text-xl font-black text-neutral">{{ $store->name }}</h1>
            </div>
        </div>
    </x-slot>

    <section class="flex min-h-[60vh] items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-[2rem] bg-white p-8 text-center shadow-sm border border-slate-200">
            <span data-testid="disabled-storefront-icon" class="mx-auto flex h-20 w-20 items-center justify-center rounded-lg bg-warning/10 text-warning">
                <x-heroicon-o-building-storefront class="h-10 w-10" />
            </span>
            <h2 class="mt-6 text-xl font-black text-neutral">غرفه این فروشنده غیرفعال است</h2>
            <p class="mt-3 text-sm leading-7 text-slate-500">این غرفه در حال حاضر برای بازدید عمومی فعال نیست.</p>
            <a href="{{ route('listings.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-3 text-sm font-black text-white transition hover:bg-primary-600">
                <x-heroicon-o-magnifying-glass class="h-5 w-5" />مشاهده آگهی‌ها
            </a>
        </div>
    </section>
</x-app-layout>
