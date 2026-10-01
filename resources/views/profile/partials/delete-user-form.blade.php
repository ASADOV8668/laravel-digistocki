<section class="space-y-6">
    <header class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
            <x-heroicon-o-trash class="h-5 w-5" />
        </span>
        <div>
            <h2 class="text-lg font-black text-slate-900">حذف حساب کاربری</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">
                با حذف حساب، آگهی‌ها و اطلاعات مرتبط با حساب شما نیز حذف می‌شوند. این عملیات قابل بازگشت نیست.
            </p>
        </div>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >حذف حساب کاربری</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="space-y-6 p-6 text-right" dir="rtl">
            @csrf
            @method('delete')

            <div>
                <h2 class="text-lg font-black text-slate-900">از حذف حساب مطمئن هستید؟</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    برای تأیید حذف دائمی حساب، رمز عبور خود را وارد کنید.
                </p>
            </div>

            <div>
                <x-input-label for="password" value="رمز عبور" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-2 block w-full"
                    placeholder="رمز عبور حساب"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="flex justify-start gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">انصراف</x-secondary-button>
                <x-danger-button>حذف دائمی حساب</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
