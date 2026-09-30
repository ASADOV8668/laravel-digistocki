<x-guest-layout title="ساخت حساب کاربری">
    @php($step = $step ?? 'mobile')
    <div class="mb-6 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary"><x-heroicon-o-user-plus class="h-7 w-7" /></span><h1 class="mt-4 text-xl font-black text-slate-900">ثبت‌نام با موبایل</h1><p class="mt-2 text-xs text-slate-500">{{ $step === 'mobile' ? 'شماره موبایل خود را برای شروع وارد کنید.' : ($step === 'otp' ? 'کد ارسال‌شده را برای تأیید شماره وارد کنید.' : 'اطلاعات حساب خود را تکمیل کنید.') }}</p></div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($step === 'mobile')
        <form method="POST" action="{{ route('register') }}" class="space-y-5">@csrf
            <div><x-input-label for="mobile" value="شماره موبایل" /><x-text-input id="mobile" aria-describedby="mobile-help mobile-error" class="mt-2 block w-full" type="tel" name="mobile" :value="old('mobile')" required autofocus autocomplete="tel" inputmode="tel" dir="ltr" placeholder="09301303005" /><p id="mobile-help" class="mt-1 text-[11px] text-slate-400">فرمت مجاز: دقیقاً ۱۱ رقم و با ۰۹، مانند 09301303005</p><x-input-error id="mobile-error" :messages="$errors->get('mobile')" class="mt-2" /></div>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-arrow-left-on-rectangle class="h-5 w-5" />دریافت کد تأیید</button>
        </form>
    @elseif ($step === 'otp')
        <form method="POST" action="{{ route('register.otp.verify') }}" class="space-y-5">@csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-center text-sm font-bold text-slate-700" dir="ltr">{{ $mobile }}</div>
            <div><x-input-label for="otp" value="رمز یکبار مصرف" /><x-text-input id="otp" aria-describedby="otp-expiry otp-error" class="mt-2 block w-full text-center tracking-[0.5em]" type="text" name="otp" required autofocus inputmode="numeric" maxlength="5" dir="ltr" placeholder="00000" /><x-input-error id="otp-error" :messages="$errors->get('otp')" class="mt-2" /></div>
            <p id="otp-expiry" class="text-center text-xs font-bold text-slate-500">اعتبار کد: <span id="otp-countdown" role="timer" aria-live="polite" aria-atomic="true" data-expires-at="{{ $otpExpiresAt }}" class="text-primary">در حال محاسبه...</span></p>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-check-circle class="h-5 w-5" />تأیید شماره موبایل</button>
        </form>
        <form id="otp-resend-form" method="POST" action="{{ route('register.otp.resend') }}" class="mt-3" hidden>@csrf<button type="submit" class="w-full rounded-xl border border-primary/20 bg-primary-50 px-4 py-3 text-sm font-black text-primary transition hover:bg-primary/10">ارسال مجدد کد</button></form>
    @else
        <form method="POST" action="{{ route('register') }}" class="space-y-4">@csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-center text-sm font-bold text-slate-700" dir="ltr">{{ $mobile }} <span class="mr-2 text-success">تأیید شد</span></div>
            <div><x-input-label for="name" value="نام و نام خانوادگی" /><x-text-input id="name" aria-describedby="name-error" class="mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="مثلاً علی رضایی" /><x-input-error id="name-error" :messages="$errors->get('name')" class="mt-2" /></div>
            <div><x-input-label for="national_id" value="کد ملی (اختیاری)" /><x-text-input id="national_id" aria-describedby="national-id-error" class="mt-2 block w-full" type="text" name="national_id" :value="old('national_id')" inputmode="numeric" maxlength="10" dir="ltr" placeholder="0012345678" /><x-input-error id="national-id-error" :messages="$errors->get('national_id')" class="mt-2" /></div>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-user-plus class="h-5 w-5" />تکمیل ثبت‌نام و ورود</button>
        </form>
    @endif

    <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-500">قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ route('login') }}" class="font-black text-primary hover:text-primary-600">وارد شوید</a></div>
    @if ($step === 'otp')
        <script>
            (() => { const node = document.getElementById('otp-countdown'); const resend = document.getElementById('otp-resend-form'); const expiresAt = Number(node?.dataset.expiresAt || 0) * 1000; const tick = () => { const remaining = Math.max(0, expiresAt - Date.now()); const seconds = Math.ceil(remaining / 1000); if (node) node.textContent = seconds > 0 ? `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}` : 'منقضی شده'; if (seconds > 0) window.setTimeout(tick, 1000); else if (resend) resend.hidden = false; }; tick(); })();
        </script>
    @endif
</x-guest-layout>
