<x-guest-layout title="ورود به حساب کاربری">
    @php($step = $step ?? 'mobile')
    <div class="mb-6 text-center"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary"><x-heroicon-o-device-phone-mobile class="h-7 w-7" /></span><h1 class="mt-4 text-xl font-black text-slate-900">ورود با شماره موبایل</h1><p class="mt-2 text-xs text-slate-500">{{ $step === 'mobile' ? 'شماره موبایل خود را وارد کنید تا حساب شما را پیدا کنیم.' : ($step === 'password' ? 'رمز عبور یا ورود یکبار مصرف را انتخاب کنید.' : 'کد ارسال‌شده به موبایل خود را وارد کنید.') }}</p></div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($step === 'mobile')
        <form method="POST" action="{{ route('login') }}" class="space-y-5">@csrf
            <div><x-input-label for="mobile" value="شماره موبایل" /><x-text-input id="mobile" aria-describedby="mobile-help mobile-error" class="mt-2 block w-full" type="tel" name="mobile" :value="old('mobile')" required autofocus autocomplete="tel" inputmode="tel" dir="ltr" placeholder="09301303005" /><p id="mobile-help" class="mt-1 text-[11px] text-slate-400">فرمت مجاز: دقیقاً ۱۱ رقم و با ۰۹، مانند 09301303005</p><x-input-error id="mobile-error" :messages="$errors->get('mobile')" class="mt-2" /></div>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-primary-600 focus:outline-none focus:ring-4 focus:ring-primary-200"><x-heroicon-o-arrow-left-on-rectangle class="h-5 w-5" />ادامه</button>
        </form>
    @elseif ($step === 'password')
        <form method="POST" action="{{ route('login') }}" class="space-y-5">@csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-center text-sm font-bold text-slate-700" dir="ltr">{{ $mobile }}</div>
            <div><x-input-label for="password" value="رمز عبور" /><x-text-input id="password" aria-describedby="password-error" class="mt-2 block w-full" type="password" name="password" required autofocus autocomplete="current-password" /><x-input-error id="password-error" :messages="$errors->get('password')" class="mt-2" /></div>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary" name="remember"><span>مرا به خاطر بسپار</span></label>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-primary-600 focus:outline-none focus:ring-4 focus:ring-primary-200"><x-heroicon-o-lock-closed class="h-5 w-5" />ورود با رمز عبور</button>
        </form>
        <form method="POST" action="{{ route('login.otp.request') }}" class="mt-4">@csrf<input type="hidden" name="mobile" value="{{ $mobile }}"><button class="w-full rounded-xl border border-primary/20 bg-primary-50 px-4 py-3 text-sm font-black text-primary transition hover:bg-primary/10">ارسال رمز یکبار مصرف با پیامک</button></form>
    @else
        <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-5">@csrf
            <input type="hidden" name="mobile" value="{{ $mobile }}">
            <div><x-input-label for="otp" value="رمز یکبار مصرف" /><x-text-input id="otp" aria-describedby="otp-expiry otp-error" class="mt-2 block w-full text-center tracking-[0.5em]" type="text" name="otp" required autofocus inputmode="numeric" maxlength="5" dir="ltr" placeholder="00000" /><x-input-error id="otp-error" :messages="$errors->get('otp')" class="mt-2" /></div>
            <p id="otp-expiry" class="text-center text-xs font-bold text-slate-500">اعتبار کد: <span id="otp-countdown" role="timer" aria-live="polite" aria-atomic="true" data-expires-at="{{ $otpExpiresAt }}" class="text-primary">در حال محاسبه...</span></p>
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-primary-600 focus:outline-none focus:ring-4 focus:ring-primary-200"><x-heroicon-o-check-circle class="h-5 w-5" />تأیید و ورود</button>
        </form>
        <form id="otp-resend-form" method="POST" action="{{ route('login.otp.request') }}" class="mt-3" hidden>@csrf<input type="hidden" name="mobile" value="{{ $mobile }}"><button type="submit" class="w-full rounded-xl border border-primary/20 bg-primary-50 px-4 py-3 text-sm font-black text-primary transition hover:bg-primary/10">ارسال مجدد کد</button></form>
    @endif

    <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-500">حساب کاربری ندارید؟ <a href="{{ route('register') }}" class="font-black text-primary hover:text-primary-600">ثبت‌نام با موبایل</a></div>
    @if ($step === 'otp')
        <script>
            (() => { const node = document.getElementById('otp-countdown'); const resend = document.getElementById('otp-resend-form'); const expiresAt = Number(node?.dataset.expiresAt || 0) * 1000; const tick = () => { const remaining = Math.max(0, expiresAt - Date.now()); const seconds = Math.ceil(remaining / 1000); if (node) node.textContent = seconds > 0 ? `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}` : 'منقضی شده'; if (seconds > 0) window.setTimeout(tick, 1000); else if (resend) resend.hidden = false; }; tick(); })();
        </script>
    @endif
</x-guest-layout>
