@props(['unreadNotifications' => 0])
<div x-data="{ open: false }" @open-sidebar.window="open = true" @keydown.escape.window="open = false" class="relative z-50">
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-neutral/50" @click="open = false" aria-hidden="true"></div>
    <aside x-show="open" x-transition:enter="transition-all duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition-all duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 w-80 max-w-[85vw] overflow-y-auto bg-white p-5 shadow-2xl" aria-label="منوی اصلی">
        <div class="mb-7 flex items-center justify-between">
            <x-logo />
            <button type="button" @click="open = false" aria-label="بستن منو" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100"><x-heroicon-o-x-mark class="h-6 w-6" /></button>
        </div>

        @auth
            <div class="mb-6 rounded-2xl bg-neutral p-4 text-white">
                <div class="mb-2 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary text-lg font-black">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                    <div class="min-w-0"><p class="truncate font-bold">{{ auth()->user()->name }}</p><p class="truncate text-xs text-white/60">{{ auth()->user()->mobile ?? auth()->user()->email }}</p></div>
                </div>
            </div>
        @else
            <div class="mb-6 rounded-2xl bg-primary-50 p-4"><p class="font-bold text-neutral">به Digistocki خوش آمدید</p><a href="{{ route('login') }}" class="mt-2 inline-flex text-sm font-bold text-primary">ورود به حساب</a></div>
        @endauth

        <nav class="space-y-1">
            <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-home class="h-5 w-5" /> خانه</a>
            @auth
                <a href="{{ url('/dashboard') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-rectangle-stack class="h-5 w-5" /> آگهی‌های من</a>
                <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-bell class="h-5 w-5" /> اعلان‌ها @if ($unreadNotifications)<span class="mr-auto rounded-full bg-primary px-2 py-0.5 text-[10px] font-black text-white">{{ $unreadNotifications }}</span>@endif</a>
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-flag class="h-5 w-5" /> گزارش‌های من</a>
                <a href="{{ route('storefront.edit') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-building-storefront class="h-5 w-5" /> غرفه شما</a>
            @endauth
            <a href="{{ auth()->check() ? route('favorites.index') : route('login') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-heart class="h-5 w-5" /> علاقه‌مندی‌ها</a>
            <a href="{{ url('/profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-user-circle class="h-5 w-5" /> پروفایل</a>
            <a href="{{ route('support.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-primary-50 hover:text-primary"><x-heroicon-o-chat-bubble-left-right class="h-5 w-5" /> ارتباط با پشتیبانی</a>

            @auth
                @if (auth()->user()->isAdmin())
                    <div class="my-3 border-t border-slate-100"></div>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-xl bg-accent/10 px-3 py-3 text-sm font-bold text-accent"><x-heroicon-o-shield-check class="h-5 w-5" /> پنل مدیریت</a>
                    <a href="{{ route('admin.listings.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-neutral hover:bg-accent/10 hover:text-accent"><x-heroicon-o-rectangle-stack class="h-5 w-5" /> مدیریت آگهی‌ها</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-error hover:bg-red-50"><x-heroicon-o-arrow-left-start-on-rectangle class="h-5 w-5" /> خروج</button>
                </form>
            @endauth
        </nav>
    </aside>
</div>
