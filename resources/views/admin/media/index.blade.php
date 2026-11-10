<x-admin-layout title="رسانه‌ها">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <x-heroicon-o-photo class="h-6 w-6" />
                </span>
                <div>
                    <h2 class="text-xl font-black text-slate-900">رسانه‌ها</h2>
                    <p class="mt-1 text-xs text-slate-400">مدیریت و مشاهده تصاویر ذخیره‌شده در کتابخانه رسانه</p>
                </div>
            </div>
            <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center">
                <strong class="block text-lg font-black text-slate-900">{{ number_format($media->total()) }}</strong>
                <span class="text-[11px] font-bold text-slate-500">رسانه در نتایج</span>
            </div>
        </div>
    </x-slot>

    <section class="space-y-6">
        <div class="admin-card p-5">
            <div class="mb-4 flex items-center gap-2">
                <x-heroicon-o-funnel class="h-5 w-5 text-primary" />
                <div>
                    <h3 class="text-sm font-black text-slate-900">جستجوی رسانه‌ها</h3>
                    <p class="mt-1 text-[11px] text-slate-400">نام فایل، نام کاربر یا شماره موبایل را جستجو کنید.</p>
                </div>
            </div>
            <form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass
                        class="pointer-events-none absolute right-3 top-3 h-5 w-5 text-slate-400" />
                    <input name="q" value="{{ request('q') }}" placeholder="نام فایل، نام کاربر یا شماره موبایل"
                        autocomplete="off"
                        class="w-full rounded-xl border-slate-200 py-3 pr-10 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="flex gap-2">
                    <button class="flex-1 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-primary">
                        جستجو
                    </button>
                    @if (request()->filled('q'))
                        <a href="{{ route('admin.media.index') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-200">
                            پاک‌کردن
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-right text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-black text-slate-500">
                        <tr>
                            <th class="px-5 py-4">تصویر</th>
                            <th class="px-5 py-4">نام فایل</th>
                            <th class="px-5 py-4">حجم</th>
                            <th class="px-5 py-4">آپلودکننده</th>
                            <th class="px-5 py-4">زمان آپلود</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($media as $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <a href="{{ $item->getUrl() }}" target="_blank" rel="noopener noreferrer"
                                        class="group block h-16 w-16 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                        <img src="{{ $item->hasGeneratedConversion('thumb') ? $item->getUrl('thumb') : $item->getUrl() }}"
                                            alt="{{ $item->name }}"
                                            class="h-full w-full object-cover transition group-hover:scale-105">
                                    </a>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="max-w-[260px] truncate font-black text-slate-800" title="{{ $item->file_name }}">
                                        {{ $item->file_name }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $item->mime_type ?: 'نوع نامشخص' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-700">
                                    {{ number_format($item->size / 1048576, 2) }} مگابایت
                                </td>
                                <td class="px-5 py-4">
                                    @if ($item->model instanceof \App\Models\User)
                                        <p class="font-bold text-slate-700">{{ $item->model->name }}</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ $item->model->mobile }}</p>
                                    @else
                                        <span class="text-xs text-slate-400">کاربر حذف‌شده</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="font-bold text-slate-700">{{ \App\Support\PersianNumber::digits(\App\Support\PersianDate::format($item->created_at)) }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ \App\Support\PersianNumber::digits(\App\Support\PersianDate::human($item->created_at)) }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400">رسانه‌ای پیدا نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-admin.pagination :paginator="$media" route="admin.media.index" :per-page-options="$perPageOptions" />
    </section>
</x-admin-layout>
