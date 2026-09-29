@php($systemOptions = app(\App\Services\SystemOptions::class))
<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $systemOptions->pageTitle($title ?? 'پنل مدیریت') }}</title>
        <meta name="theme-color" content="#1c1a26">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="mobile-shell min-h-screen bg-slate-50">
            <header class="sticky top-0 z-30 border-b border-slate-700 bg-neutral text-white shadow-sm"><div class="flex h-16 items-center justify-between px-4"><a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2"><x-logo /><span class="text-sm font-black">پنل مدیریت</span></a><a href="{{ route('home') }}" class="rounded-xl p-2 text-white/70 transition hover:bg-white/10 hover:text-white" aria-label="بازگشت به سایت"><x-heroicon-o-arrow-left class="h-5 w-5" /></a></div></header>
            <div class="border-b border-slate-200 bg-white px-4 py-3"><nav class="flex gap-2 overflow-x-auto whitespace-nowrap text-xs font-bold"><a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2 text-neutral hover:bg-accent/10 hover:text-accent">داشبورد</a><a href="{{ route('admin.listings.index') }}" class="rounded-lg px-3 py-2 text-neutral hover:bg-accent/10 hover:text-accent">آگهی‌ها</a><a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2 text-neutral hover:bg-accent/10 hover:text-accent">کاربران</a><a href="{{ route('admin.reports.index') }}" class="rounded-lg px-3 py-2 text-neutral hover:bg-accent/10 hover:text-accent">گزارش‌ها</a><a href="{{ route('admin.brands.index') }}" class="rounded-lg px-3 py-2 text-neutral hover:bg-accent/10 hover:text-accent">کاتالوگ</a><a href="{{ route('admin.settings.edit') }}" class="rounded-lg px-3 py-2 text-neutral hover:bg-accent/10 hover:text-accent">تنظیمات</a></nav></div>
            @if (session('status'))<div class="mx-4 mt-4 rounded-xl bg-secondary/10 p-3 text-sm font-bold text-success">{{ session('status') }}</div>@endif
            @isset($header)<div class="px-4 pt-5">{{ $header }}</div>@endisset
            <main>{{ $slot }}</main>
        </div>
    </body>
</html>
