@php
    $adminImport = request()->routeIs('admin.listing-imports.test.*');
    $title = $adminImport ? 'تست ایمپورت آگهی' : 'ایمپورت آگهی';
    $previewUrl = $adminImport && $seller
        ? route('admin.listing-imports.test.preview', ['seller' => $seller->id])
        : route('listings.import.preview');
@endphp
<x-dynamic-component :component="$adminImport ? 'admin-layout' : 'app-layout'" :title="$title">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary"><x-heroicon-o-arrow-down-tray class="h-6 w-6" /></span>
            <div>
                @if ($parser)
                    <p class="text-xs font-bold text-primary">قالب {{ $parser->title }}</p>
                @endif
                <h1 class="mt-1 text-xl font-black text-neutral">{{ $title }}</h1>
            </div>
        </div>
    </x-slot>

    <section class="space-y-6 px-4 py-6">
        @if ($errors->any())
            <x-flowbite-alert type="danger">{{ $errors->first() }}</x-flowbite-alert>
        @endif
        @if ($adminImport)
            <form method="GET" action="{{ route('admin.listing-imports.test.create') }}" class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-5 shadow-sm">
                <div>
                    <label for="import-seller" class="mb-2 block text-sm font-bold">فروشنده</label>
                    <select id="import-seller" name="seller" required class="rounded-lg border-slate-200 text-sm">
                        <option value="">انتخاب فروشنده</option>
                        @foreach ($sellers as $option)
                            <option value="{{ $option->id }}" @selected($seller?->id === $option->id)>
                                {{ $option->name }} · {{ $option->storefront->name }} · {{ $option->mobile }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white">انتخاب فروشنده</button>
            </form>
            @if ($restriction && ! $errors->has('seller'))
                <x-flowbite-alert type="danger">{{ $restriction }}</x-flowbite-alert>
            @endif
        @endif

        @if ($parser && ! $restriction)
            <div class="rounded-lg border border-primary/15 bg-primary/5 p-4 text-sm leading-7 text-neutral dark:border-primary/30 dark:bg-primary/10 dark:text-slate-100">
                متن موجودی را از تلگرام یا واتساپ کپی و اینجا وارد کنید. قبل از ثبت نهایی، همهٔ ردیف‌ها و تصویر هر آگهی را بررسی خواهید کرد.
            </div>
            <form method="POST" action="{{ $previewUrl }}" class="space-y-5 rounded-lg bg-white p-5 shadow-sm dark:bg-slate-900">
                @csrf
                <div>
                    <label for="import-text" class="mb-2 block text-sm font-bold text-neutral dark:text-slate-100">متن موجودی</label>
                    <textarea id="import-text" name="text" rows="16" dir="auto" required class="w-full rounded-lg border-slate-200 text-sm leading-7 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800" placeholder="متن آگهی را اینجا Paste کنید...">{{ old('text') }}</textarea>
                </div>
                <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                    <div>
                        <label for="listing-date" class="mb-2 block text-sm font-bold text-neutral dark:text-slate-100">تاریخ شمسی آگهی</label>
                        <input id="listing-date" type="text" name="listing_date" dir="ltr" maxlength="10" placeholder="۱۴۰۵/۰۹/۰۵"
                            value="{{ old('listing_date', \App\Support\PersianNumber::digits(\App\Support\PersianDate::format(today('Asia/Tehran')))) }}"
                            aria-describedby="listing-date-format" class="w-full rounded-lg border-slate-200 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800">
                        <p id="listing-date-format" class="mt-1 text-xs text-slate-500">سال/ماه/روز</p>
                    </div>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-magnifying-glass class="h-5 w-5" />ساخت پیش‌نمایش</button>
                </div>
            </form>
        @endif
    </section>
</x-dynamic-component>
