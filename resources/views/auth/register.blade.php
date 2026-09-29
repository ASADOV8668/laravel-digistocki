<x-guest-layout title="ساخت حساب کاربری">
    <form method="POST" action="{{ route('register') }}" class="space-y-4">@csrf
        <div><x-input-label for="name" value="نام و نام خانوادگی" /><x-text-input id="name" class="mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="مثلاً علی رضایی" /><x-input-error :messages="$errors->get('name')" class="mt-2" /></div>
        <div><x-input-label for="mobile" value="شماره موبایل" /><x-text-input id="mobile" class="mt-2 block w-full" type="tel" name="mobile" :value="old('mobile')" autocomplete="tel" placeholder="09120000000" /><p class="mt-1 text-[11px] text-slate-400">برای ورود سریع و دریافت کدهای تأیید استفاده می‌شود.</p><x-input-error :messages="$errors->get('mobile')" class="mt-2" /></div>
        <div><x-input-label for="email" value="ایمیل (اختیاری)" /><x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" autocomplete="email" placeholder="email@example.com" /><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
        <div><x-input-label for="password" value="رمز عبور" /><x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        <div><x-input-label for="password_confirmation" value="تکرار رمز عبور" /><x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" /></div>
        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-user-plus class="h-5 w-5" />ساخت حساب</button>
    </form>
    <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-500">قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ route('login') }}" class="font-black text-primary hover:text-primary-600">وارد شوید</a></div>
</x-guest-layout>
