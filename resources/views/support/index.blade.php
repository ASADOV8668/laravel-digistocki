<x-app-layout title="ارتباط با پشتیبانی">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary"><x-heroicon-o-chat-bubble-left-right class="h-6 w-6" /></span>
            <div><p class="text-xs font-bold text-primary">راهنمایی و همراهی</p><h1 class="mt-1 text-xl font-black text-neutral">ارتباط با پشتیبانی</h1></div>
        </div>
    </x-slot>

    <section class="space-y-5 px-4 py-6">
        <div class="relative overflow-hidden rounded-[2rem] bg-neutral p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="absolute -left-12 -top-12 h-40 w-40 rounded-full bg-primary/20 blur-3xl"></div>
            <div class="relative">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-primary"><x-heroicon-o-lifebuoy class="h-7 w-7" /></span>
                <h2 class="mt-5 text-2xl font-black">ما در کنار شما هستیم</h2>
                <p class="mt-2 max-w-xs text-sm leading-7 text-white/65">برای پیگیری آگهی، گزارش مشکل یا دریافت راهنمایی درباره خرید و فروش موبایل با ما در تماس باشید.</p>
            </div>
        </div>

        <div class="rounded-[2rem] bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="mb-4 flex items-center justify-between gap-3"><div><h2 class="font-black text-neutral">راه‌های ارتباطی</h2><p class="mt-1 text-[11px] text-slate-400">برای شروع گفتگو، یکی از گزینه‌های زیر را انتخاب کنید.</p></div><span class="rounded-full bg-success/10 px-3 py-1 text-[10px] font-black text-success">پاسخ‌گویی فعال</span></div>
            <div class="space-y-3">
                @if ($settings['support_phone'])
                    <a href="tel:{{ $settings['support_phone'] }}" class="group flex items-center gap-3 rounded-2xl bg-primary-50 p-4 transition hover:-translate-y-0.5 hover:shadow-sm">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-primary shadow-sm"><x-heroicon-o-phone class="h-5 w-5" /></span><span class="min-w-0 flex-1"><span class="block text-xs text-slate-500">شماره تماس</span><span class="mt-1 block font-black text-neutral" dir="ltr">{{ $settings['support_phone'] }}</span></span><x-heroicon-o-arrow-left class="h-4 w-4 text-primary transition group-hover:-translate-x-1" />
                    </a>
                @endif
                @if ($settings['support_email'])
                    <a href="mailto:{{ $settings['support_email'] }}" class="group flex items-center gap-3 rounded-2xl bg-accent/10 p-4 transition hover:-translate-y-0.5 hover:shadow-sm">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-accent shadow-sm"><x-heroicon-o-envelope class="h-5 w-5" /></span><span class="min-w-0 flex-1"><span class="block text-xs text-slate-500">ایمیل پشتیبانی</span><span class="mt-1 block font-black text-neutral" dir="ltr">{{ $settings['support_email'] }}</span></span><x-heroicon-o-arrow-left class="h-4 w-4 text-accent transition group-hover:-translate-x-1" />
                    </a>
                @endif
                @if ($settings['support_office_address'])
                    <div class="flex items-start gap-3 rounded-2xl bg-secondary/10 p-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-success shadow-sm"><x-heroicon-o-map-pin class="h-5 w-5" /></span><span><span class="block text-xs text-slate-500">آدرس دفتر</span><span class="mt-1 block font-bold leading-7 text-neutral">{{ $settings['support_office_address'] }}</span></span></div>
                @endif
                @if (! $settings['support_phone'] && ! $settings['support_email'] && ! $settings['support_office_address'])
                    <div class="rounded-2xl bg-slate-50 p-6 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-200 text-slate-500"><x-heroicon-o-information-circle class="h-6 w-6" /></span><p class="mt-3 text-sm font-bold text-slate-600">اطلاعات تماس پشتیبانی هنوز ثبت نشده است.</p><p class="mt-1 text-xs leading-6 text-slate-400">مدیریت به‌زودی راه‌های ارتباطی را در این بخش قرار می‌دهد.</p></div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-primary/10 bg-primary-50/60 p-4 text-xs leading-6 text-slate-600"><span class="font-black text-primary">نکته امنیتی:</span> برای حفظ امنیت حساب، رمز عبور یا کد تأیید خود را در اختیار هیچ‌کس قرار ندهید.</div>
    </section>
</x-app-layout>
