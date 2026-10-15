<section>
    @php($needsPasswordSetup = session('password_setup_available') === true || ! $user->hasSetPassword())
    <header>
        <div class="flex items-center gap-2"><x-heroicon-o-lock-closed class="h-5 w-5 text-info" />
            <h2 class="text-lg font-black text-neutral">امنیت حساب</h2>
        </div>
        <p class="mt-1 text-xs leading-6 text-slate-500">رمز عبور باید حداقل ۸ کاراکتر و شامل ترکیبی از حروف بزرگ و کوچک انگلیسی و اعداد باشد.</p>
    </header>
    <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4">@csrf @method('put')
        @if ($needsPasswordSetup)
            <div class="rounded-lg border border-info/20 bg-info/5 px-4 py-3 text-sm leading-7 text-info">
                شما هنوز رمز عبوری انتخاب نکرده‌اید؛ رمز عبور جدید خود را وارد کنید.
            </div>
        @else
            <div>
                <x-input-label for="update_password_current_password" value="رمز عبور فعلی" />
                <x-text-input id="update_password_current_password" name="current_password" type="password"
                    class="mt-2 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>
        @endif
        <div>
            <x-input-label for="update_password_password" value="رمز عبور جدید" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-2 block w-full"
                autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="update_password_password_confirmation" value="تکرار رمز عبور" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                class="mt-2 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>
        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-info px-4 py-2.5 text-sm font-black text-white transition hover:bg-info/80">
                تغییر رمز عبور
            </button>
            @if (session('status') === 'password-updated')
                <span class="text-xs font-bold text-success">ذخیره شد.</span>
            @endif
        </div>
    </form>
</section>
