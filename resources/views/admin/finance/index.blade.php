<x-admin-layout title="مدیریت مالی">
    <x-slot name="header">
        <h1 class="text-xl font-black">مدیریت مالی</h1>
    </x-slot>

    <section class="admin-card space-y-4 p-5" aria-labelledby="finance-status">
        <h2 id="finance-status" class="font-bold">وضعیت بخش مالی</h2>
        <p class="text-sm leading-7 text-slate-600 dark:text-slate-300">
            می‌توانید سفارش بسته را برای فروشگاه به‌صورت پیش‌نویس ثبت کنید. پرداخت، کیف پول، فاکتورها، بازپرداخت و گزارش درآمد در مرحلهٔ توسعه‌اند.
        </p>
        <a href="{{ route('admin.finance.orders.index') }}" class="font-bold text-primary underline">پیش‌نویس سفارش بسته</a>
    </section>
</x-admin-layout>
