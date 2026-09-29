@php($systemOptions = app(\App\Services\SystemOptions::class))
<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $systemOptions->pageTitle($title ?? null) }}</title>
        <meta name="theme-color" content="#eb073f">
        <link rel="manifest" href="{{ asset('manifest.json') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="mobile-shell relative pb-24">
            <x-sidebar />
            <x-top-bar />
            @isset($header)<div class="px-4 pt-5">{{ $header }}</div>@endisset
            <main>{{ $slot }}</main>
            <x-bottom-nav />
        </div>
        <script>if ('serviceWorker' in navigator) { window.addEventListener('load', () => navigator.serviceWorker.register('{{ asset('sw.js') }}')); }</script>
    </body>
</html>
