@php($systemOptions = app(\App\Services\SystemOptions::class))
<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $systemOptions->pageTitle($title ?? 'پنل مدیریت') }}</title>
        <meta name="theme-color" content="#111827">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/logo-192.png') }}">
        @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    </head>
    <body
        x-data="{ loaded: true, darkMode: false, sidebarOpen: false, sidebarCollapsed: false }"
        x-init="const storedTheme = localStorage.getItem('admin-dark-mode'); const storedSidebar = localStorage.getItem('admin-sidebar-collapsed'); darkMode = storedTheme ? JSON.parse(storedTheme) : false; sidebarCollapsed = storedSidebar ? JSON.parse(storedSidebar) : false; $watch('darkMode', value => localStorage.setItem('admin-dark-mode', JSON.stringify(value))); $watch('sidebarCollapsed', value => localStorage.setItem('admin-sidebar-collapsed', JSON.stringify(value)))"
        :class="darkMode ? 'dark bg-gray-900' : 'bg-gray-50'"
        class="font-sans antialiased"
    >
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-[9998] bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

        <div class="tailadmin-shell" data-admin-ui="tailadmin-v2">
            <div class="flex h-screen overflow-hidden">
            <aside
                @click.outside="if (window.innerWidth < 1024) sidebarOpen = false"
                :class="[sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0', sidebarCollapsed ? 'lg:w-[90px]' : 'lg:w-[290px]']"
                class="fixed inset-y-0 right-0 z-[9999] flex w-[290px] flex-col overflow-y-auto border-l border-gray-200 bg-white px-5 shadow-xl transition-all duration-300 dark:border-gray-800 dark:bg-gray-900 lg:static lg:shadow-none"
            >
                <div class="flex h-20 shrink-0 items-center justify-between border-b border-gray-100 dark:border-gray-800">
                    <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3" title="داشبورد مدیریت">
                        <img src="{{ asset('images/logo-header.svg') }}" alt="Digistocki" class="h-10 w-10 shrink-0">
                        <span x-show="!sidebarCollapsed" x-transition class="min-w-0">
                            <span class="block truncate text-base font-black text-gray-900 dark:text-white">Digistocki</span>
                            <span class="block truncate text-[10px] font-medium text-gray-400">پنل مدیریت سامانه</span>
                        </span>
                    </a>
                    <button type="button" @click="sidebarOpen = false" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-white/5 dark:hover:text-white lg:hidden" aria-label="بستن منو">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto py-6">
                    <p x-show="!sidebarCollapsed" class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-400">نمای کلی</p>
                    <a href="{{ route('admin.dashboard') }}" title="داشبورد" class="menu-item group {{ request()->routeIs('admin.dashboard') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-squares-2x2 class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">داشبورد</span>
                    </a>
                    <p x-show="!sidebarCollapsed" class="mb-3 mt-7 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-400">مدیریت محتوا</p>
                    <a href="{{ route('admin.listings.index') }}" title="آگهی‌ها" class="menu-item group {{ request()->routeIs('admin.listings.*') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-rectangle-stack class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.listings.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">آگهی‌ها</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" title="کاربران" class="menu-item group {{ request()->routeIs('admin.users.*') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-users class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.users.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">کاربران</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" title="گزارش‌ها" class="menu-item group {{ request()->routeIs('admin.reports.*') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-flag class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.reports.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">گزارش‌ها</span>
                    </a>

                    <p x-show="!sidebarCollapsed" class="mb-3 mt-7 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-400">کاتالوگ</p>
                    <a href="{{ route('admin.brands.index') }}" title="کاتالوگ گوشی" class="menu-item group {{ request()->routeIs('admin.brands.*', 'admin.phone-models.*', 'admin.attributes.*') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-device-phone-mobile class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.brands.*', 'admin.phone-models.*', 'admin.attributes.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">کاتالوگ گوشی</span>
                    </a>
                    <a href="{{ route('admin.phone-models.index') }}" title="مدل‌های گوشی" class="menu-item group {{ request()->routeIs('admin.phone-models.*') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-cpu-chip class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.phone-models.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">مدل‌های گوشی</span>
                    </a>
                    <a href="{{ route('admin.attributes.index') }}" title="ویژگی‌های گوشی" class="menu-item group {{ request()->routeIs('admin.attributes.*') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-adjustments-horizontal class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.attributes.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">ویژگی‌های گوشی</span>
                    </a>

                    <p x-show="!sidebarCollapsed" class="mb-3 mt-7 px-3 text-[10px] font-bold uppercase tracking-[0.16em] text-gray-400">سیستم</p>
                    <a href="{{ route('admin.settings.edit') }}" title="تنظیمات" class="menu-item group {{ request()->routeIs('admin.settings.*') ? 'menu-item-active' : 'menu-item-inactive' }}" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-cog-6-tooth class="h-5 w-5 shrink-0 {{ request()->routeIs('admin.settings.*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" />
                        <span x-show="!sidebarCollapsed">تنظیمات</span>
                    </a>
                    <a href="{{ route('home') }}" title="مشاهده سایت" class="menu-item group menu-item-inactive" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <x-heroicon-o-arrow-top-right-on-square class="menu-item-icon-inactive h-5 w-5 shrink-0" />
                        <span x-show="!sidebarCollapsed">مشاهده سایت</span>
                    </a>
                </nav>

                <div class="border-t border-gray-100 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3 dark:bg-white/5" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-black text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                        <div x-show="!sidebarCollapsed" class="min-w-0">
                            <p class="truncate text-sm font-bold text-gray-800 dark:text-white">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-400">مدیر سیستم</p>
                        </div>
                        <form x-show="!sidebarCollapsed" method="POST" action="{{ route('logout') }}" class="mr-auto">
                            @csrf
                            <button class="rounded-lg p-2 text-gray-400 transition hover:bg-white hover:text-primary dark:hover:bg-white/10" aria-label="خروج"><x-heroicon-o-arrow-left-start-on-rectangle class="h-5 w-5" /></button>
                        </form>
                    </div>
                    <button type="button" @click="sidebarCollapsed = !sidebarCollapsed" class="mt-3 hidden w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-bold text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-white lg:flex" :title="sidebarCollapsed ? 'باز کردن منو' : 'جمع کردن منو'">
                        <x-heroicon-o-chevron-double-left class="h-4 w-4 transition-transform" x-bind:class="sidebarCollapsed ? 'rotate-180' : ''" />
                        <span x-show="!sidebarCollapsed">جمع کردن منو</span>
                    </button>
                </div>
            </aside>

            <div class="relative flex min-w-0 flex-1 flex-col overflow-x-hidden overflow-y-auto">
                <header class="sticky top-0 z-40 flex h-20 w-full shrink-0 items-center border-b border-gray-200 bg-white/90 px-4 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                        <button type="button" @click="sidebarOpen = true" class="rounded-lg border border-gray-200 p-2 text-gray-500 transition hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:hover:bg-white/5 dark:hover:text-white lg:hidden" aria-label="باز کردن منو"><x-heroicon-o-bars-3 class="h-5 w-5" /></button>
                        <div class="hidden min-w-0 md:block">
                            <p class="text-xs font-medium text-gray-400">خوش آمدید، {{ auth()->user()->name }}</p>
                            <h1 class="mt-1 truncate text-lg font-black text-gray-900 dark:text-white">{{ $title ?? 'پنل مدیریت' }}</h1>
                        </div>
                        <div class="relative hidden max-w-md flex-1 lg:block">
                            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            <input type="search" placeholder="جستجو در پنل مدیریت..." class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 pr-10 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/10 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <button type="button" @click="darkMode = !darkMode" class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5" :aria-label="darkMode ? 'حالت روشن' : 'حالت تاریک'">
                            <x-heroicon-o-sun x-show="darkMode" class="h-5 w-5" />
                            <x-heroicon-o-moon x-show="!darkMode" class="h-5 w-5" />
                        </button>
                        <button type="button" class="relative flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5" aria-label="اعلان‌ها">
                            <x-heroicon-o-bell class="h-5 w-5" />
                            <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-primary ring-2 ring-white dark:ring-gray-900"></span>
                        </button>
                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-lg p-1.5 transition hover:bg-gray-50 dark:hover:bg-white/5" aria-label="منوی کاربر">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-sm font-black text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                                <x-heroicon-o-chevron-down class="hidden h-4 w-4 text-gray-400 sm:block" />
                            </button>
                            <div x-cloak x-show="open" x-transition @click.outside="open = false" class="absolute left-0 top-12 z-50 w-56 rounded-xl border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                <div class="border-b border-gray-100 px-3 py-2 dark:border-gray-700"><p class="truncate text-sm font-bold text-gray-800 dark:text-white">{{ auth()->user()->name }}</p><p class="truncate text-xs text-gray-400">{{ auth()->user()->mobile }}</p></div>
                                <a href="{{ route('home') }}" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"><x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" /> مشاهده سایت</a>
                                <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10"><x-heroicon-o-arrow-left-start-on-rectangle class="h-4 w-4" /> خروج</button></form>
                            </div>
                        </div>
                    </div>
                </header>

                @if (session('status'))
                    <div class="mx-4 mt-5 rounded-xl border border-secondary/20 bg-secondary/10 p-4 text-sm font-bold text-success sm:mx-6 lg:mx-8">{{ session('status') }}</div>
                @endif

                @isset($header)
                    <div class="px-4 pt-6 sm:px-6 lg:px-8">{{ $header }}</div>
                @endisset

                <main class="tailadmin-main mx-auto w-full max-w-[1600px] p-4 pb-20 sm:p-6 md:pb-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
            </div>
        </div>
    </body>
</html>
