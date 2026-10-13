<nav id="public-bottom-navigation"
    class="fixed bottom-0 left-1/2 z-30 h-16 w-full max-w-md -translate-x-1/2 border-t border-slate-200 bg-white/95 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-1 shadow-sm backdrop-blur lg:max-w-6xl"
    aria-label="ناوبری پایین">
    <div class="mx-auto grid h-full max-w-lg grid-cols-5 font-medium lg:max-w-6xl">
        <a href="{{ url('/') }}" @class([
            'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('home'),
            'text-neutral hover:text-primary' => !request()->routeIs('home'),
        ])
            @if (request()->routeIs('home')) aria-current="page" @endif><x-heroicon-o-home
                class="mb-1 h-6 w-6 group-hover:text-primary" /><span>خانه</span></a>
        <a href="{{ url('/listings') }}" @class([
            'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('listings.index'),
            'text-neutral hover:text-primary' => !request()->routeIs('listings.index'),
        ])
            @if (request()->routeIs('listings.index')) aria-current="page" @endif><x-heroicon-o-magnifying-glass
                class="mb-1 h-6 w-6 group-hover:text-primary" /><span>جستجو</span></a>
        @php($listingAction = auth()->check() ? (auth()->user()->storefront ? url('/listings/create') : route('storefront.edit')) : route('login'))
        <a href="{{ $listingAction }}"
            class="group -mt-7 inline-flex flex-col items-center justify-center px-2 text-xs font-bold text-primary"><span
                class="mb-1 flex h-14 w-14 items-center justify-center rounded-full border-4 border-slate-50 bg-primary text-white shadow-lg transition group-hover:bg-primary-600"><x-heroicon-o-plus
                    class="h-7 w-7" /></span><span>{{ auth()->check() && !auth()->user()->storefront ? 'ثبت فروشگاه' : 'ثبت آگهی' }}</span></a>
        <a href="{{ route('storefront.index') }}" @class([
            'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('storefront.index'),
            'text-neutral hover:text-primary' => !request()->routeIs(
                'storefront.index'),
        ])
            @if (request()->routeIs('storefront.index')) aria-current="page" @endif><x-heroicon-o-building-storefront
                class="mb-1 h-6 w-6 group-hover:text-primary" /><span>فروشگاه‌ها</span></a>
        <a href="{{ auth()->check() ? url('/profile') : route('login') }}" @class([
            'group inline-flex flex-col items-center justify-center rounded-lg px-2 text-xs transition focus:outline-none focus:ring-4 focus:ring-primary/20',
            'bg-primary-50 text-primary' => request()->routeIs('profile.*'),
            'text-neutral hover:text-primary' => !request()->routeIs('profile.*'),
        ])
            @if (request()->routeIs('profile.*')) aria-current="page" @endif><x-heroicon-o-user
                class="mb-1 h-6 w-6 group-hover:text-primary" /><span>پروفایل</span></a>
    </div>
</nav>
