<x-admin-layout title="ایمپورت آگهی">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold text-info">مدیریت داده‌های ورودی</p>
                <h1 class="mt-1 text-xl font-black text-slate-900">ایمپورت آگهی</h1>
            </div>
            <a href="{{ route('admin.listing-imports.create') }}" class="rounded-xl bg-info px-4 py-2 text-sm font-bold text-white">ایجاد قالب</a>
        </div>
    </x-slot>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
        <div class="admin-card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-black text-slate-900">قالب‌های Parse</h2>
                <p class="mt-1 text-xs text-slate-400">برای فعال‌سازی ایمپورت، قالب را در صفحهٔ ویرایش فروشگاه انتخاب کنید.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($parsers as $parser)
                    <a href="{{ route('admin.listing-imports.index', ['parser' => $parser->key]) }}"
                        class="flex items-center justify-between gap-3 p-5 transition hover:bg-slate-50 {{ $selected?->is($parser) ? 'bg-info/5' : '' }}">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-info/10 text-info">
                                <x-heroicon-o-document-text class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-slate-800">{{ $parser->title }}</p>
                                <p class="mt-1 truncate text-xs text-slate-400" dir="ltr">{{ $parser->key }}</p>
                            </div>
                        </div>
                        <x-heroicon-o-chevron-left class="h-5 w-5 shrink-0 text-slate-300" />
                    </a>
                @endforeach
            </div>
        </div>

        @if ($selected)
            <div class="admin-card overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-info">نمونه متن ورودی</p>
                            <h2 class="mt-1 text-lg font-black text-slate-900">{{ $selected->title }}</h2>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700">{{ $selected->is_active ? 'فعال' : 'غیرفعال' }}</span>
                            <a href="{{ route('admin.listing-imports.edit', $selected) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-info">ویرایش قالب</a>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-7 text-slate-500">{{ $selected->description }}</p>
                </div>
                <div class="bg-slate-950 p-5">
                    <pre dir="ltr" class="max-h-[34rem] overflow-auto whitespace-pre-wrap text-left text-sm leading-8 text-slate-100">{{ $selected->sample_text }}</pre>
                </div>
                <div class="flex items-center gap-2 bg-slate-50 px-5 py-4 text-xs text-slate-500">
                    <x-heroicon-o-information-circle class="h-4 w-4 text-info" />
                    ردیف‌های تکراری دقیق در پیش‌نمایش فقط یک‌بار نمایش داده می‌شوند.
                </div>
            </div>
        @endif
    </section>
</x-admin-layout>
