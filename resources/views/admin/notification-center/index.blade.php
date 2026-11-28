<x-admin-layout title="مرکز اعلان‌ها">
    <x-slot name="header">
        <h1 class="text-xl font-black">مرکز اعلان‌ها</h1>
    </x-slot>

    <section class="admin-card space-y-4 p-5" aria-labelledby="notification-center-inbox">
        <h2 id="notification-center-inbox" class="font-bold">صندوق اعلان‌های مدیریت</h2>
        <p class="text-sm leading-7 text-slate-600 dark:text-slate-300">
            اعلان‌های فعلی حساب مدیریت از صندوق اعلان‌ها در دسترس‌اند.
        </p>
        <a href="{{ route('admin.notifications.index') }}" class="inline-block rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white">
            مشاهدهٔ اعلان‌های مدیریت
        </a>
    </section>
</x-admin-layout>
