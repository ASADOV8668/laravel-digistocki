<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>صفحه پیدا نشد | دیجی استوک</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center justify-center px-6 py-16">
        <section class="w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-200/60 sm:p-14">
            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-rose-50 text-4xl font-black text-rose-500">۴۰۴</div>
            <p class="mt-8 text-sm font-bold text-rose-500">این صفحه در دسترس نیست</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">چیزی که دنبالش بودید پیدا نشد</h1>
            <p class="mx-auto mt-4 max-w-xl leading-8 text-slate-500">ممکن است آدرس تغییر کرده باشد یا آگهی موردنظر دیگر منتشر نباشد. از مسیرهای زیر ادامه دهید.</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('home') }}" class="rounded-2xl bg-rose-500 px-6 py-3 font-bold text-white transition hover:bg-rose-600">بازگشت به خانه</a>
                <a href="{{ route('listings.index') }}" class="rounded-2xl border border-slate-200 px-6 py-3 font-bold text-slate-700 transition hover:border-rose-200 hover:text-rose-500">مشاهده آگهی‌ها</a>
            </div>
        </section>
    </main>
</body>
</html>
