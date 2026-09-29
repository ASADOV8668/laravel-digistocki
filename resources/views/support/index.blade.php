<x-app-layout title="ارتباط با پشتیبانی">
    <x-slot name="header"><h1 class="text-xl font-black text-neutral">ارتباط با پشتیبانی</h1></x-slot>
    <section class="space-y-4 px-4 py-6">
        <div class="rounded-3xl bg-neutral p-6 text-white shadow-sm"><x-heroicon-o-chat-bubble-left-right class="h-10 w-10 text-primary" /><h2 class="mt-4 text-xl font-black">ما در کنار شما هستیم</h2><p class="mt-2 text-sm leading-7 text-white/70">برای پیگیری آگهی یا دریافت راهنمایی، از راه‌های ارتباطی زیر با تیم پشتیبانی تماس بگیرید.</p></div>
        <div class="space-y-3 rounded-3xl bg-white p-5 shadow-sm">
            @if ($settings['support_phone'])<a href="tel:{{ $settings['support_phone'] }}" class="flex items-center gap-3 rounded-2xl bg-primary-50 p-4"><x-heroicon-o-phone class="h-6 w-6 text-primary" /><span><span class="block text-xs text-slate-500">شماره تماس</span><span class="font-bold text-neutral" dir="ltr">{{ $settings['support_phone'] }}</span></span></a>@endif
            @if ($settings['support_email'])<a href="mailto:{{ $settings['support_email'] }}" class="flex items-center gap-3 rounded-2xl bg-accent/10 p-4"><x-heroicon-o-envelope class="h-6 w-6 text-accent" /><span><span class="block text-xs text-slate-500">ایمیل</span><span class="font-bold text-neutral" dir="ltr">{{ $settings['support_email'] }}</span></span></a>@endif
            @if ($settings['support_office_address'])<div class="flex items-start gap-3 rounded-2xl bg-secondary/10 p-4"><x-heroicon-o-map-pin class="h-6 w-6 shrink-0 text-success" /><span><span class="block text-xs text-slate-500">آدرس دفتر</span><span class="font-bold leading-7 text-neutral">{{ $settings['support_office_address'] }}</span></span></div>@endif
            @if (! $settings['support_phone'] && ! $settings['support_email'] && ! $settings['support_office_address'])<p class="text-center text-sm text-slate-500">اطلاعات تماس پشتیبانی هنوز توسط مدیریت ثبت نشده است.</p>@endif
        </div>
    </section>
</x-app-layout>
