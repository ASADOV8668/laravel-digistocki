<!DOCTYPE html>
@php($siteTitle = app(\App\Services\SystemOptions::class)->get('site_title'))
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>خطای موقت | {{ $siteTitle }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center justify-center px-6 py-16">
        <section class="w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-200/60 sm:p-14">
            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-amber-50 text-3xl font-black text-amber-500">۵۰۰</div>
            <p class="mt-8 text-sm font-bold text-amber-600">یک خطای موقت رخ داد</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">در حال برطرف‌کردن مشکل هستیم</h1>
            <p class="mx-auto mt-4 max-w-xl leading-8 text-slate-500">لطفاً چند لحظه بعد دوباره تلاش کنید. اطلاعات شما حفظ شده است.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('home') }}" class="rounded-2xl bg-amber-500 px-6 py-3 font-bold text-white transition hover:bg-amber-600">بازگشت به خانه</a>
                <a href="{{ route('support.index') }}" class="rounded-2xl border border-slate-200 px-6 py-3 font-bold text-slate-700 transition hover:border-amber-200 hover:text-amber-600">ارتباط با پشتیبانی</a>
            </div>
        </section>
    </main>
</body>
</html>
