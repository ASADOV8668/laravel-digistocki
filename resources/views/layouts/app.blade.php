@php($systemOptions = app(\App\Services\SystemOptions::class))
@php($unreadNotifications = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0)
<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php($seoTitle = $systemOptions->pageTitle($title ?? null))
        @php($seoDescription = $metaDescription ?? 'خرید و فروش مطمئن موبایل با آگهی‌های بررسی‌شده و جستجوی هوشمند در دیجی استوک.')
        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:site_name" content="{{ $systemOptions->get('site_title') }}">
        <meta name="theme-color" content="#eb073f">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/logo-192.png') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        @stack('head')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="mobile-shell relative pb-24">
            <div
                x-data="networkStatus()"
                x-init="init()"
                x-cloak
                x-show="!online"
                x-transition
                class="fixed inset-x-4 top-3 z-[60] mx-auto flex max-w-md items-center gap-3 rounded-2xl border border-warning/30 bg-warning-50 px-4 py-3 text-sm font-bold text-amber-900 shadow-lg"
                role="status"
                aria-live="polite"
            >
                <x-heroicon-o-signal-slash class="h-5 w-5 shrink-0" />
                <span>اتصال اینترنت قطع است؛ بعضی اطلاعات ممکن است به‌روز نباشند.</span>
            </div>
            <x-sidebar :unread-notifications="$unreadNotifications" />
            <x-top-bar :unread-notifications="$unreadNotifications" :show-back="! request()->routeIs('home')" />
            <div
                x-data="pwaInstallPrompt()"
                x-init="init()"
                x-cloak
                x-show="(canInstall || isIos) && !installed && !dismissed"
                class="mx-4 mt-3 rounded-2xl border border-primary/20 bg-primary-50 p-4 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <img src="{{ asset('images/logo-192.png') }}" alt="" class="h-12 w-12 rounded-2xl">
                    <div class="min-w-0 flex-1">
                        <p class="font-black text-neutral">{{ $systemOptions->get('site_title') }} را به صفحه اصلی اضافه کن</p>
                        <p x-show="!isIos" class="mt-1 text-xs leading-6 text-slate-600">برای دسترسی سریع‌تر و تجربه‌ای شبیه اپلیکیشن.</p>
                        <p x-show="isIos" class="mt-1 text-xs leading-6 text-slate-600">در Safari روی دکمه اشتراک‌گذاری بزنید و گزینه «افزودن به صفحه اصلی» را انتخاب کنید.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button x-show="!isIos" type="button" @click="install()" class="mobile-button bg-primary text-white">
                                افزودن به صفحه اصلی
                            </button>
                            <button x-show="isIos" type="button" @click="dismiss()" class="mobile-button bg-primary text-white">
                                متوجه شدم
                            </button>
                            <button type="button" @click="dismiss()" class="mobile-button bg-white text-slate-600 ring-1 ring-slate-200">
                                بعداً
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @isset($header)<div class="px-4 pt-5">{{ $header }}</div>@endisset
            <main>{{ $slot }}</main>
            <x-bottom-nav />
        </div>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('{{ asset('sw.js') }}')
                        .then((registration) => registration.update())
                        .catch(() => {});
                });
            }
        </script>
    </body>
</html>
