<x-admin-layout title="ایجاد فروشگاه">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.storefronts.index') }}"
                class="rounded-xl bg-slate-100 p-2 text-slate-500 transition hover:bg-primary/10 hover:text-primary"
                aria-label="بازگشت"><x-heroicon-o-arrow-right class="h-5 w-5" /></a>
            <div>
                <p class="text-xs font-bold text-primary">مدیریت فروشگاه‌ها</p>
                <h1 class="mt-1 text-xl font-black text-slate-900">ایجاد دستی فروشگاه</h1>
            </div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-3xl space-y-5">
        @if ($errors->any())
            <div class="rounded-2xl border border-error/20 bg-error/10 p-4 text-sm font-bold text-error" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="rounded-2xl bg-primary p-5 text-white">
            <p class="text-xs font-bold text-primary-100">کنترل مدیریتی</p>
            <h2 class="mt-1 text-lg font-black">انتخاب صاحب فروشگاه</h2>
            <p class="mt-2 text-xs leading-6 text-white/75">ابتدا کاربری را که هنوز فروشگاه ندارد انتخاب کنید؛ سپس فرم کامل اطلاعات فروشگاه، مدارک و تنظیمات نمایش برای او باز می‌شود.</p>
        </div>
        <form method="GET" action="{{ route('admin.storefronts.create') }}" autocomplete="off"
            class="admin-card space-y-5 p-5">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input name="q" value="{{ $query }}" autocomplete="off"
                    placeholder="جستجو با نام، شماره موبایل یا ایمیل"
                    class="min-w-0 flex-1 rounded-xl border-slate-200 py-3 text-sm focus:border-primary focus:ring-primary">
                <button class="rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700">جستجوی کاربر</button>
            </div>
            <div>
                <label for="store-owner" class="mb-2 block text-sm font-bold text-slate-700">صاحب فروشگاه</label>
                <select id="store-owner" name="user_id" required autocomplete="off"
                    class="w-full rounded-xl border-slate-200 py-3 text-sm focus:border-primary focus:ring-primary">
                    <option value="">انتخاب کاربر</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->mobile ?: $user->email }}</option>
                    @endforeach
                </select>
                @if ($users->isEmpty())
                    <p class="mt-2 text-xs font-bold text-warning">کاربری بدون فروشگاه با این جستجو پیدا نشد.</p>
                @else
                    <p class="mt-2 text-xs text-slate-400">فقط کاربرانی نمایش داده می‌شوند که هنوز فروشگاه ندارند.</p>
                @endif
            </div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.storefronts.index') }}"
                    class="rounded-xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-600">انصراف</a>
                <button class="rounded-xl bg-primary px-5 py-3 text-sm font-black text-white disabled:opacity-50"
                    @disabled($users->isEmpty())>ادامه و تکمیل اطلاعات فروشگاه</button>
            </div>
        </form>
    </section>
</x-admin-layout>
