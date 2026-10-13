@php
    $status = $store->is_admin_disabled
        ? ['مسدود توسط مدیریت', 'bg-error/10 text-error']
        : ($store->isPending()
            ? ['در انتظار بررسی', 'bg-warning/10 text-warning']
            : ($store->isRejected()
                ? ['ردشده', 'bg-error/10 text-error']
                : ($store->is_enabled ? ['فعال', 'bg-success/10 text-success'] : ['غیرفعال', 'bg-slate-100 text-slate-500'])));
    $socials = [
        'اینستاگرام' => $store->instagram_url,
        'تلگرام' => $store->telegram_url,
        'واتساپ' => $store->whatsapp_url,
        'بله' => $store->bale_url,
        'روبیکا' => $store->rubika_url,
    ];
@endphp
<x-admin-layout :title="'جزئیات فروشگاه '.$store->name">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <x-heroicon-o-building-storefront class="h-6 w-6" />
                </span>
                <div>
                    <p class="text-xs font-bold text-primary">پرونده کامل متقاضی</p>
                    <h2 class="mt-1 text-xl font-black text-slate-900">{{ $store->name }}</h2>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.storefronts.index') }}" class="rounded-xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-600 transition hover:bg-slate-200">بازگشت به لیست</a>
                <a href="{{ route('admin.storefronts.edit', $user) }}" class="rounded-xl bg-primary px-4 py-3 text-sm font-black text-white transition hover:bg-primary-700">ویرایش اطلاعات</a>
            </div>
        </div>
    </x-slot>

    <section class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[1fr_1.4fr]">
            <article class="admin-card space-y-5 p-6">
                <div class="flex items-center gap-4">
                    @if ($store->logo_path)
                        <img src="{{ asset('storage/'.$store->logo_path) }}" alt="{{ $store->name }}" class="h-20 w-20 rounded-3xl object-cover">
                    @else
                        <span class="flex h-20 w-20 items-center justify-center rounded-3xl bg-primary/10 text-2xl font-black text-primary">{{ mb_substr($store->name, 0, 1) }}</span>
                    @endif
                    <div class="min-w-0">
                        <h3 class="text-lg font-black text-slate-900">{{ $store->name }}</h3>
                        <p class="mt-1 text-sm text-slate-400" dir="ltr">/store/{{ $store->slug }}</p>
                        <span class="mt-3 inline-flex rounded-full px-3 py-1.5 text-xs font-black {{ $status[1] }}">{{ $status[0] }}</span>
                    </div>
                </div>
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-400">نام و نام خانوادگی</dt><dd class="font-bold text-slate-800">{{ trim($store->first_name.' '.$store->last_name) ?: $user->name }}</dd></div>
                    <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-400">شماره موبایل</dt><dd class="font-bold text-slate-800" dir="ltr">{{ $user->mobile }}</dd></div>
                    <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-400">ایمیل</dt><dd class="font-bold text-slate-800" dir="ltr">{{ $user->email ?: 'ثبت نشده' }}</dd></div>
                    <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-400">کد ملی</dt><dd class="font-bold text-slate-800" dir="ltr">{{ $store->national_id ?: ($user->national_id ?: 'ثبت نشده') }}</dd></div>
                    <div class="flex items-center justify-between gap-4 py-3"><dt class="text-slate-400">شماره تماس فروشگاه</dt><dd class="font-bold text-slate-800" dir="ltr">{{ $store->contact_phone ?: 'ثبت نشده' }}</dd></div>
                </dl>
            </article>

            <article class="admin-card space-y-5 p-6">
                <h3 class="text-base font-black text-slate-900">اطلاعات درخواست فروشگاه</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><p class="text-xs text-slate-400">استان</p><p class="mt-1 font-bold text-slate-800">{{ $store->province?->name ?: 'ثبت نشده' }}</p></div>
                    <div><p class="text-xs text-slate-400">شهر</p><p class="mt-1 font-bold text-slate-800">{{ $store->city?->name ?: 'ثبت نشده' }}</p></div>
                    <div><p class="text-xs text-slate-400">شماره صنفی</p><p class="mt-1 font-bold text-slate-800" dir="ltr">{{ $store->business_license_number ?: 'ثبت نشده' }}</p></div>
                    <div><p class="text-xs text-slate-400">اعتبار جواز کسب</p><p class="mt-1 font-bold text-slate-800">{{ $store->business_license_expires_at ? \App\Support\PersianDate::format($store->business_license_expires_at, 'Y/m/d') : 'ثبت نشده' }}</p></div>
                </div>
                <div><p class="text-xs text-slate-400">آدرس دقیق</p><p class="mt-1 whitespace-pre-line leading-7 text-slate-800">{{ $store->address ?: 'ثبت نشده' }}</p></div>
                @if ($store->business_license_path)
                    <a href="{{ asset('storage/'.$store->business_license_path) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-primary/10 px-4 py-3 text-sm font-black text-primary hover:bg-primary/20">
                        <x-heroicon-o-document-magnifying-glass class="h-5 w-5" />مشاهده تصویر جواز کسب
                    </a>
                @else
                    <p class="rounded-xl bg-slate-50 p-3 text-sm text-slate-400">تصویر جواز کسب ثبت نشده است.</p>
                @endif
            </article>
        </div>

        <article class="admin-card p-6">
            <h3 class="text-base font-black text-slate-900">کانال‌های ارتباطی</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($socials as $label => $value)
                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-400">{{ $label }}</p><p class="mt-1 break-all font-bold text-slate-800" dir="ltr">{{ $value ?: 'ثبت نشده' }}</p></div>
                @endforeach
            </div>
        </article>

        @if ($store->rejection_reason)
            <article class="rounded-2xl border border-error/20 bg-error/5 p-6">
                <h3 class="font-black text-error">علت رد درخواست</h3>
                <p class="mt-2 leading-7 text-slate-700">{{ $store->rejection_reason }}</p>
            </article>
        @endif
    </section>
</x-admin-layout>
