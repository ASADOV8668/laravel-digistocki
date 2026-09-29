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
            @isset($header)<div class="px-4 pt-5">{{ $header }}</div>@endisset
            <main>{{ $slot }}</main>
            <x-bottom-nav />
        </div>
        <script>if ('serviceWorker' in navigator) { window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}')); }</script>
    </body>
</html>
