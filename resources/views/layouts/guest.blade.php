<!DOCTYPE html>
<html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Digistocki') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-neutral antialiased">
        <div class="mobile-shell flex min-h-screen flex-col items-center justify-center px-5 py-8">
            <x-logo class="mb-8" />
            <div class="w-full rounded-2xl bg-white p-6 shadow-sm">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
