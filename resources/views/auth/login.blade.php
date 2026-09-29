<x-guest-layout title="ورود به حساب کاربری">
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('login') }}" class="space-y-5">@csrf
        <div><x-input-label for="login" value="موبایل یا ایمیل" /><x-text-input id="login" class="mt-2 block w-full" type="text" name="login" :value="old('login', old('email'))" required autofocus autocomplete="username" placeholder="09120000000 یا email@example.com" /><x-input-error :messages="$errors->get('login')" class="mt-2" /></div>
        <div><div class="flex items-center justify-between"><x-input-label for="password" value="رمز عبور" />@if (Route::has('password.request'))<a class="text-xs font-bold text-primary hover:text-primary-600" href="{{ route('password.request') }}">رمز عبور را فراموش کرده‌اید؟</a>@endif</div><x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        <label for="remember_me" class="flex items-center gap-2 text-xs font-bold text-slate-500"><input id="remember_me" type="checkbox" class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary" name="remember"><span>مرا به خاطر بسپار</span></label>
        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-arrow-left-on-rectangle class="h-5 w-5" />ورود به حساب</button>
    </form>
    <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-500">حساب کاربری ندارید؟ <a href="{{ route('register') }}" class="font-black text-primary hover:text-primary-600">ثبت‌نام کنید</a></div>
</x-guest-layout>
