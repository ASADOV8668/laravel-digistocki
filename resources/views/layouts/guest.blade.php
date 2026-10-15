@props(['title' => 'حساب کاربری'])
@php($systemOptions = app(\App\Services\SystemOptions::class))
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">@php($seoTitle = $systemOptions->pageTitle($title))
    <meta name="description" content="{{ $systemOptions->siteDescription() }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $systemOptions->siteDescription() }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $systemOptions->get('site_title') }}">
    <title>{{ $seoTitle }}</title>
    <meta name="theme-color" content="#eb073f">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/logo-192.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <script>
        (() => {
            const theme = document.cookie.match(/(?:^|;\s*)public_theme=([^;]+)/)?.[1];
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.dataset.theme = theme === 'dark' ? 'dark' : 'light';
        })();
    </script>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 font-sans text-neutral antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen bg-slate-50 px-4 py-6 dark:bg-slate-950 sm:flex sm:items-center sm:justify-center sm:py-10">
        <div
            class="grid w-full max-w-4xl overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-800 dark:bg-slate-900 sm:grid-cols-[0.85fr_1.15fr]">
            <div class="relative hidden overflow-hidden bg-slate-900 p-8 text-white sm:block">
                <div class="absolute -left-16 -top-16 h-48 w-48 rounded-full bg-primary/25 blur-3xl"></div>
                <div class="absolute -bottom-16 -right-16 h-48 w-48 rounded-full bg-secondary/15 blur-3xl"></div>
                <div class="relative flex h-full flex-col"><a href="{{ route('home') }}"
                        class="inline-flex items-center gap-2 text-sm font-bold text-white/70 transition hover:text-white"><x-heroicon-o-arrow-right
                            class="h-5 w-5" />بازگشت به سایت</a>
                    <div class="mt-auto"><x-logo class="mb-8" />
                        <p class="text-sm leading-7 text-white/65">خرید و فروش مطمئن موبایل، با آگهی‌های بررسی‌شده و
                            جستجوی هوشمند.</p>
                        <div class="mt-8 space-y-3 text-xs font-bold text-white/75">
                            <div class="flex items-center gap-2"><x-heroicon-o-check-badge
                                    class="h-5 w-5 text-primary" />آگهی‌های بررسی‌شده</div>
                            <div class="flex items-center gap-2"><x-heroicon-o-shield-check
                                    class="h-5 w-5 text-secondary" />تجربه امن‌تر</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-9">
                <div class="mb-7 flex items-center justify-between sm:hidden"><x-logo /><div class="flex items-center gap-1"><button
                            type="button" data-theme-toggle aria-pressed="false" aria-label="فعال‌کردن حالت شب"
                            class="rounded-lg p-2 text-slate-500 dark:text-slate-300" ><x-heroicon-o-moon data-theme-icon-light
                                class="h-5 w-5" /><x-heroicon-o-sun data-theme-icon-dark class="hidden h-5 w-5" /></button><a href="{{ route('home') }}"
                        class="rounded-lg bg-slate-100 p-2 text-slate-500 dark:bg-slate-800 dark:text-slate-300" aria-label="بازگشت"><x-heroicon-o-arrow-left
                            class="h-5 w-5" /></a></div></div>
                <div class="mb-6">
                    <p class="text-xs font-bold text-primary">{{ $systemOptions->get('site_title') }}</p>
                    <h1 class="mt-2 text-2xl font-black text-neutral dark:text-slate-100">{{ $title }}</h1>
                    <p class="mt-2 text-xs text-slate-400">اطلاعاتت را وارد کن تا ادامه بدهیم.</p>
                </div>{{ $slot }}
            </div>
        </div>
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
