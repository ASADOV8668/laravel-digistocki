<x-admin-layout title="افزودن کاربر">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}" class="rounded-xl bg-slate-100 p-2 text-slate-500 transition hover:bg-info/10 hover:text-info"><x-heroicon-o-arrow-right class="h-5 w-5" /></a>
            <div><p class="text-xs font-bold text-info">مدیریت کاربران</p><h1 class="mt-1 text-xl font-black text-slate-900">افزودن کاربر جدید</h1></div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-4xl space-y-6">
        @if ($errors->any())
            <div class="rounded-2xl border border-error/20 bg-error/10 p-4 text-sm font-bold text-error"><ul class="space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('admin.users.store') }}" class="admin-card space-y-6 p-6">
            @csrf
            <div><h2 class="text-base font-black text-slate-900">اطلاعات حساب</h2><p class="mt-1 text-xs text-slate-400">شماره موبایل شناسه یکتای کاربر در کل سیستم است.</p></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="mb-2 block text-sm font-bold text-slate-700">نام و نام خانوادگی</label><input name="name" value="{{ old('name') }}" required class="w-full rounded-xl border-slate-200 text-sm focus:border-info focus:ring-info"></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">شماره موبایل</label><input name="mobile" value="{{ old('mobile') }}" inputmode="tel" required placeholder="09123456789" class="w-full rounded-xl border-slate-200 text-sm focus:border-info focus:ring-info"></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">کد ملی <span class="text-xs font-normal text-slate-400">اختیاری</span></label><input name="national_id" value="{{ old('national_id') }}" inputmode="numeric" maxlength="10" class="w-full rounded-xl border-slate-200 text-sm focus:border-info focus:ring-info"></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">ایمیل <span class="text-xs font-normal text-slate-400">اختیاری</span></label><input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-info focus:ring-info"></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">رمز عبور</label><input type="password" name="password" required class="w-full rounded-xl border-slate-200 text-sm focus:border-info focus:ring-info"></div>
                <div><label class="mb-2 block text-sm font-bold text-slate-700">تکرار رمز عبور</label><input type="password" name="password_confirmation" required class="w-full rounded-xl border-slate-200 text-sm focus:border-info focus:ring-info"></div>
            </div>
            <div class="border-t border-slate-100 pt-5"><h2 class="text-base font-black text-slate-900">دسترسی‌ها</h2><div class="mt-4 grid gap-4 md:grid-cols-3"><div><label class="mb-2 block text-sm font-bold text-slate-700">نقش کاربر</label><select name="role" class="w-full rounded-xl border-slate-200 text-sm focus:border-info focus:ring-info"><option value="user" @selected(old('role', 'user') === 'user')>کاربر عادی</option><option value="admin" @selected(old('role') === 'admin')>مدیر سیستم</option></select></div><label class="flex items-center gap-2 self-end rounded-xl bg-slate-50 p-3 text-sm font-bold text-slate-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300 text-info focus:ring-info"> حساب فعال باشد</label><label class="flex items-center gap-2 self-end rounded-xl bg-slate-50 p-3 text-sm font-bold text-slate-700"><input type="checkbox" name="can_post_listings" value="1" @checked(old('can_post_listings', true)) class="rounded border-slate-300 text-info focus:ring-info"> اجازه ثبت آگهی</label></div></div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('admin.users.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-600">انصراف</a><button class="rounded-xl bg-info px-5 py-3 text-sm font-black text-white transition hover:bg-info/90">ایجاد کاربر</button></div>
        </form>
    </section>
</x-admin-layout>
