@props(['unreadNotifications' => 0, 'showBack' => true])
@php($siteTitle = app(\App\Services\SystemOptions::class)->get('site_title'))

<header class="sticky top-0 z-30 border-b border-slate-100 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
    <div class="flex h-16 items-center justify-between px-4">
        <button type="button" data-drawer-target="public-sidebar" data-drawer-show="public-sidebar"
            data-drawer-placement="left" aria-controls="public-sidebar" aria-label="باز کردن منو"
                    class="rounded-lg p-2 text-neutral transition-all duration-300 hover:bg-primary-50 hover:text-primary focus:outline-none focus:ring-4 focus:ring-primary/20 dark:text-slate-100 dark:hover:bg-primary/10">
            <x-heroicon-o-bars-3 class="h-6 w-6" />
        </button>

        <a href="{{ route('home') }}" aria-label="{{ $siteTitle }}"
            class="mx-auto inline-flex items-center justify-center rounded-lg p-1">
            <img src="{{ asset('images/logo.png') }}" alt="{{ $siteTitle }}" class="h-10 w-10 object-contain" />
        </a>

        <div class="flex items-center gap-1">
            @if ($showBack)
                <button type="button" onclick="history.back()" aria-label="بازگشت"
                    class="rounded-lg p-2 text-neutral transition-all duration-300 hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-primary/20 dark:text-slate-100 dark:hover:bg-slate-800">
                    <x-heroicon-o-arrow-left class="h-6 w-6" />
                </button>
            @elseif (auth()->check() && app(\App\Services\ListingRules::class)->canCreate(auth()->user()))
                <a href="{{ route('profile.edit') }}" aria-label="پروفایل"
                    class="rounded-lg p-2 text-neutral transition-all duration-300 hover:bg-primary-50 hover:text-primary focus:outline-none focus:ring-4 focus:ring-primary/20 dark:text-slate-100 dark:hover:bg-primary/10">
                    <x-heroicon-o-user class="h-5 w-5" />
                </a>
            @elseif (auth()->check())
                <a href="{{ route('notifications.index') }}" aria-label="اعلان‌ها"
                    class="relative rounded-lg p-2 text-neutral transition-all duration-300 hover:bg-primary-50 hover:text-primary focus:outline-none focus:ring-4 focus:ring-primary/20 dark:text-slate-100 dark:hover:bg-primary/10">
                    <x-heroicon-o-bell class="h-5 w-5" />
                    @if (auth()->check() && $unreadNotifications)
                        <span data-unread-notifications-badge
                            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[9px] font-black text-white"
                            aria-label="{{ $unreadNotifications }} اعلان خوانده‌نشده">{{ $unreadNotifications > 9 ? '۹+' : $unreadNotifications }}</span>
                    @endif
                </a>
            @else
                <button type="button" data-theme-toggle aria-pressed="false" aria-label="فعال‌کردن حالت شب"
                    class="rounded-lg p-2 text-neutral transition-all duration-300 hover:bg-primary-50 hover:text-primary focus:outline-none focus:ring-4 focus:ring-primary/20 dark:text-slate-100 dark:hover:bg-primary/10">
                    <x-heroicon-o-moon data-theme-icon-light class="h-5 w-5" />
                    <x-heroicon-o-sun data-theme-icon-dark class="hidden h-5 w-5" />
                </button>
            @endif
        </div>
    </div>
</header>
