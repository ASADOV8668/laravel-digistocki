@props(['unreadNotifications' => 0])
@php($siteTitle = app(\App\Services\SystemOptions::class)->get('site_title'))

<aside id="public-sidebar" data-drawer-backdrop="true" class="fixed left-0 top-0 z-50 h-screen w-80 max-w-[85vw] -translate-x-full overflow-y-auto bg-white p-5 shadow-2xl transition-transform" tabindex="-1" aria-labelledby="public-sidebar-title">
    <div class="mb-7 flex items-center justify-between">
        <h2 id="public-sidebar-title" class="sr-only">منوی اصلی</h2>
        <x-logo />
        <button type="button" data-drawer-hide="public-sidebar" aria-controls="public-sidebar" aria-label="بستن منو" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><x-heroicon-o-x-mark class="h-6 w-6" /></button>
    </div>

    @auth
        <div class="mb-6 rounded-lg bg-neutral p-4 text-white">
            <div class="mb-2 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary text-lg font-black">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                <div class="min-w-0"><p class="truncate font-bold">{{ auth()->user()->name }}</p><p class="truncate text-xs text-white/60">{{ auth()->user()->mobile ?? auth()->user()->email }}</p></div>
            </div>
        </div>
    @else
        <div class="mb-6 rounded-lg bg-primary-50 p-4"><p class="font-bold text-neutral">به {{ $siteTitle }} خوش آمدید</p><a href="{{ route('login') }}" class="mt-2 inline-flex text-sm font-bold text-primary">ورود به حساب</a></div>
    @endauth

    <nav class="space-y-1" aria-label="ناوبری اصلی">
        <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-home class="h-5 w-5" /> خانه</a>
        @auth
            <a href="{{ url('/dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-rectangle-stack class="h-5 w-5" /> آگهی‌های من</a>
            <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-bell class="h-5 w-5" /> اعلان‌ها @if ($unreadNotifications)<span class="mr-auto rounded-full bg-primary px-2 py-0.5 text-[10px] font-black text-white">{{ $unreadNotifications }}</span>@endif</a>
            <a href="{{ route('reports.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-flag class="h-5 w-5" /> گزارش‌های من</a>
            <a href="{{ route('storefront.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-building-storefront class="h-5 w-5" /> غرفه شما</a>
        @endauth
        <a href="{{ auth()->check() ? route('favorites.index') : route('login') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-heart class="h-5 w-5" /> علاقه‌مندی‌ها</a>
        <a href="{{ url('/profile') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-user-circle class="h-5 w-5" /> پروفایل</a>
        <a href="{{ route('support.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-chat-bubble-left-right class="h-5 w-5" /> ارتباط با پشتیبانی</a>

        @auth
            @if (auth()->user()->isAdmin())
                <div class="my-3 border-t border-slate-100"></div>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg bg-accent/10 px-3 py-3 text-sm font-bold text-accent"><x-heroicon-o-shield-check class="h-5 w-5" /> پنل مدیریت</a>
                <a href="{{ route('admin.listings.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-neutral hover:bg-accent/10 hover:text-accent"><x-heroicon-o-rectangle-stack class="h-5 w-5" /> مدیریت آگهی‌ها</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-sm font-semibold text-error hover:bg-red-50"><x-heroicon-o-arrow-left-start-on-rectangle class="h-5 w-5" /> خروج</button>
            </form>
        @endauth
    </nav>
</aside>
