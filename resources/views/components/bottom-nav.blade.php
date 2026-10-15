<nav id="public-bottom-navigation"
    class="fixed bottom-0 left-1/2 z-30 h-16 w-full max-w-md -translate-x-1/2 border-t border-slate-200 bg-white/95 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-950/95"
    aria-label="ناوبری پایین">
@php($canPostListings = auth()->check() && app(\App\Services\ListingRules::class)->canCreate(auth()->user()))
@php($unreadChatLabel = \App\Support\PersianNumber::digits($unreadChats > 99 ? '+99' : $unreadChats))
    <div class="mx-auto grid h-full max-w-lg grid-cols-5 font-medium">
        <a href="{{ route('listings.index') }}" @class([
            'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('listings.index'),
                'text-neutral hover:text-primary dark:text-slate-100' => !request()->routeIs('listings.index'),
        ])
            @if (request()->routeIs('listings.index')) aria-current="page" @endif><x-heroicon-o-magnifying-glass
                class="mb-1 h-6 w-6 group-hover:text-primary" /><span>آگهی‌ها</span></a>
        <a href="{{ auth()->check() ? route('favorites.index') : route('login') }}" @class([
            'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('favorites.index'),
                'text-neutral hover:text-primary dark:text-slate-100' => !request()->routeIs('favorites.index'),
        ])
            @if (request()->routeIs('favorites.index')) aria-current="page" @endif><x-heroicon-o-heart
                class="mb-1 h-6 w-6 group-hover:text-primary" /><span>موردعلاقه</span></a>
        @if ($canPostListings)
            <a href="{{ route('listings.create') }}"
                class="group -mt-7 inline-flex flex-col items-center justify-center px-2 text-xs font-bold text-primary"><span
                    class="mb-1 flex h-14 w-14 items-center justify-center rounded-full border-4 border-slate-50 bg-primary text-white shadow-lg transition group-hover:bg-primary-600 dark:border-slate-950"><x-heroicon-o-plus
                        class="h-7 w-7" /></span><span>ثبت آگهی</span></a>
        @endif
        <a href="{{ route('storefront.index') }}" @class([
            'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('storefront.index'),
                'text-neutral hover:text-primary dark:text-slate-100' => !request()->routeIs(
                'storefront.index'),
        ])
            @if (request()->routeIs('storefront.index')) aria-current="page" @endif><x-heroicon-o-building-storefront
                class="mb-1 h-6 w-6 group-hover:text-primary" /><span>فروشگاه‌ها</span></a>
        <a href="{{ auth()->check() ? route('chats.index') : route('login') }}" @class([
            'group relative inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('chats.*'),
            'text-neutral hover:text-primary dark:text-slate-100' => !request()->routeIs('chats.*'),
        ]) @if (request()->routeIs('chats.*')) aria-current="page" @endif>
            <span class="relative"><x-heroicon-o-chat-bubble-left-right class="mb-1 h-6 w-6 group-hover:text-primary" />
                @if ($unreadChats > 0)
                    <span class="absolute -right-3 -top-3 min-w-5 rounded-full bg-error px-1.5 py-0.5 text-center text-[9px] font-black leading-4 text-white">
                        {{ $unreadChatLabel }}
                    </span>
                @endif
            </span>
            <span>چت</span>
        </a>
        @if (! $canPostListings)
            <a href="{{ auth()->check() ? route('profile.edit') : route('login') }}" @class([
                'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
                'bg-primary-50 text-primary' => request()->routeIs('profile.*'),
                    'text-neutral hover:text-primary dark:text-slate-100' => !request()->routeIs('profile.*'),
            ])
                @if (request()->routeIs('profile.*')) aria-current="page" @endif><x-heroicon-o-user
                    class="mb-1 h-6 w-6 group-hover:text-primary" /><span>پروفایل</span></a>
        @endif
    </div>
</nav>
