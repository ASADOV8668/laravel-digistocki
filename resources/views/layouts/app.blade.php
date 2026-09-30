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
            <x-sidebar :unread-notifications="$unreadNotifications" />
            <x-top-bar :unread-notifications="$unreadNotifications" />
            <div
                x-data="pwaInstallPrompt()"
                x-init="init()"
                x-cloak
                x-show="canInstall && !dismissed"
                class="mx-4 mt-3 rounded-2xl border border-primary/20 bg-primary-50 p-4 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <img src="{{ asset('images/logo-192.png') }}" alt="" class="h-12 w-12 rounded-2xl">
                    <div class="min-w-0 flex-1">
                        <p class="font-black text-neutral">دیجی استوک را به صفحه اصلی اضافه کن</p>
                        <p class="mt-1 text-xs leading-6 text-slate-600">برای دسترسی سریع‌تر و تجربه‌ای شبیه اپلیکیشن.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" @click="install()" class="mobile-button bg-primary text-white">
                                افزودن به صفحه اصلی
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
        <script>if ('serviceWorker' in navigator) { window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}')); }</script>
    </body>
</html>
