<x-admin-layout title="دسترسی و سوابق">
    <x-slot name="header">
        <h1 class="text-xl font-black">دسترسی و سوابق</h1>
    </x-slot>

    <section class="admin-card space-y-4 p-5" aria-labelledby="access-records-status">
        <h2 id="access-records-status" class="font-bold">نقش‌ها و سوابق</h2>
        @if (\Illuminate\Support\Facades\Route::has('admin.roles.index') && (auth()->user()?->isSeniorAdmin() ?? false))
            <a href="{{ route('admin.roles.index') }}" class="text-sm font-bold text-primary underline">
                مدیریت نقش‌ها و مجوزها
            </a>
        @endif
        <p class="text-sm leading-7 text-slate-600 dark:text-slate-300">
            سوابق فعالیت کاربران، اقدامات مدیران و رویدادهای امنیتی در مرحلهٔ توسعه‌اند.
        </p>
    </section>
</x-admin-layout>
