<x-app-layout title="خانه">
    <section class="space-y-6 px-4 py-6">
        <div class="rounded-3xl bg-neutral p-6 text-white shadow-sm">
            <span class="rounded-full bg-primary/20 px-3 py-1 text-xs font-bold text-primary-100">بازار مطمئن موبایل</span>
            <h1 class="mt-5 text-3xl font-black leading-tight">گوشی بعدی‌ات<br><span class="text-primary">همین‌جاست.</span></h1>
            <p class="mt-3 text-sm leading-7 text-white/70">از بین آگهی‌های واقعی موبایل، مدل مناسب خودت را سریع پیدا کن.</p>

            <div class="relative mt-5" x-data="searchSuggest('{{ route('listings.autocomplete') }}')" @click.outside="open = false">
                <form action="{{ route('listings.index') }}" method="GET" class="flex gap-2">
                    <label class="relative flex-1">
                        <span class="sr-only">جستجوی آگهی</span>
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute right-3 top-3 h-5 w-5 text-slate-400" />
                        <input name="q" x-model="query" @input.debounce.300ms="search" @focus="open = suggestions.length > 0" type="search" autocomplete="off" placeholder="مثلاً آیفون ۱۳ یا سامسونگ" class="w-full rounded-xl border-0 bg-white py-3 pe-10 ps-3 text-sm text-neutral placeholder:text-slate-400 focus:ring-2 focus:ring-primary">
                    </label>
                    <button type="submit" class="rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-primary-600">جستجوی آگهی‌ها</button>
                </form>

                <div x-cloak x-show="open && (loading || suggestions.length)" class="absolute inset-x-0 top-full z-20 mt-2 overflow-hidden rounded-2xl bg-white text-neutral shadow-xl">
                    <div x-show="loading" class="px-4 py-3 text-xs text-slate-500">در حال جستجو...</div>
                    <template x-for="suggestion in suggestions" :key="suggestion.url">
                        <a :href="suggestion.url" class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-primary-50">
                            <template x-if="suggestion.image">
                                <img :src="suggestion.image" :alt="suggestion.title" class="h-11 w-11 rounded-xl object-cover">
                            </template>
                            <template x-if="!suggestion.image">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary"><x-heroicon-o-device-phone-mobile class="h-5 w-5" /></span>
                            </template>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold" x-text="suggestion.title"></span>
                                <span class="mt-1 block truncate text-xs text-slate-500" x-text="suggestion.meta"></span>
                            </span>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-black text-neutral">دسته‌های محبوب</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-bold text-primary">مشاهده همه</a>
        </div>
        <div class="grid grid-cols-3 gap-3">
            @foreach (['آیفون', 'سامسونگ', 'شیائومی'] as $brand)
                <a href="{{ route('listings.index') }}" class="rounded-2xl bg-white p-4 text-center text-sm font-bold text-neutral shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md">
                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary"><x-heroicon-o-device-phone-mobile class="h-6 w-6" /></div>
                    {{ $brand }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-black text-neutral">آخرین آگهی‌ها</h2>
            <a href="{{ route('listings.index') }}" class="text-sm font-bold text-primary">مشاهده همه</a>
        </div>
        <div class="grid grid-cols-2 gap-3">
            @forelse ($latestListings as $listing)
                <x-listing-card :listing="$listing" />
            @empty
                <div class="col-span-2 rounded-2xl bg-white p-6 text-center text-sm text-slate-500">هنوز آگهی تأییدشده‌ای ثبت نشده است.</div>
            @endforelse
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="rounded-xl bg-secondary/10 p-3 text-secondary"><x-heroicon-o-shield-check class="h-6 w-6" /></div>
                <div><h3 class="font-bold text-neutral">خرید و فروش امن‌تر</h3><p class="mt-1 text-xs leading-6 text-slate-500">آگهی‌ها پیش از انتشار توسط تیم ما بررسی می‌شوند.</p></div>
            </div>
        </div>
    </section>
</x-app-layout>
