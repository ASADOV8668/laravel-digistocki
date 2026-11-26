<x-admin-layout title="استان‌ها و شهرها">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-black text-slate-900">استان‌ها و شهرها</h2>
            <a href="{{ route('admin.locations.deletion-requests.index') }}" class="text-sm font-bold text-primary">
                {{ auth()->user()->isSeniorAdmin() ? 'درخواست‌های در انتظار تأیید' : 'درخواست‌های حذف من' }}
            </a>
        </div>
    </x-slot>

    <section class="space-y-6">
        <x-admin.feedback />

        <div class="grid gap-5 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.locations.provinces.store') }}" class="admin-card space-y-4 p-5">
                @csrf
                <h3 class="font-black">افزودن استان</h3>
                <label class="block text-sm">نام استان
                    <input name="name" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-200">
                </label>
                <label class="block text-sm">پیش‌شماره تلفن
                    <input name="tel_prefix" required maxlength="3" inputmode="numeric" dir="ltr" class="mt-1 w-full rounded-xl border-slate-200">
                </label>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">افزودن استان</button>
            </form>

            @if ($province)
                <div class="admin-card space-y-4 p-5">
                    <form method="GET" action="{{ route('admin.locations.index') }}" class="flex flex-wrap items-end gap-3">
                        <label class="min-w-0 flex-1 text-sm">استان
                            <select name="province" class="mt-1 w-full rounded-xl border-slate-200">
                                @foreach ($provinces as $item)
                                    <option value="{{ $item->id }}" @selected($item->id === $province->id)>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold">نمایش شهرها</button>
                    </form>

                    <form method="POST" action="{{ route('admin.locations.provinces.update', $province) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <label class="block text-sm">نام استان
                            <input name="name" value="{{ $province->name }}" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-200">
                        </label>
                        <label class="block text-sm">پیش‌شماره تلفن
                            <input name="tel_prefix" value="{{ $province->tel_prefix }}" required maxlength="3" inputmode="numeric" dir="ltr"
                                class="mt-1 w-full rounded-xl border-slate-200">
                        </label>
                        <button class="rounded-lg bg-accent px-4 py-2 text-sm font-bold text-white">ذخیره استان</button>
                    </form>
                    <form method="POST" action="{{ route('admin.locations.deletion-requests.store', $province) }}" class="space-y-2">
                        @csrf
                        <label class="block text-sm">دلیل درخواست حذف
                            <textarea name="reason" required maxlength="1000" class="mt-1 w-full rounded-xl border-slate-200">{{ old('reason') }}</textarea>
                        </label>
                        <button class="rounded-lg border border-error/30 px-4 py-2 text-sm font-bold text-error">ثبت درخواست حذف استان</button>
                    </form>
                </div>
            @endif
        </div>

        @if ($province)
            <form method="POST" action="{{ route('admin.locations.cities.store', $province) }}" class="admin-card space-y-4 p-5">
                @csrf
                <h3 class="font-black">افزودن شهر به {{ $province->name }}</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="text-sm">نام شهر
                        <input name="name" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-200">
                    </label>
                    <label class="text-sm">شهرستان
                        <input name="county" list="location-counties" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-200">
                        <span class="mt-1 block text-xs text-slate-500">شهرستان موجود را انتخاب کنید یا نام شهرستان جدید را بنویسید.</span>
                    </label>
                    <datalist id="location-counties">
                        @foreach ($counties as $county)
                            <option value="{{ $county->name }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">افزودن شهر</button>
            </form>

            <div class="admin-card space-y-4 p-5">
                <h3 class="font-black">شهرهای {{ $province->name }}</h3>
                @forelse ($cities as $city)
                    <form method="POST" action="{{ route('admin.locations.cities.update', $city) }}"
                        class="flex flex-wrap items-end gap-3 border-b border-slate-100 pb-3">
                        @csrf
                        @method('PATCH')
                        <label class="min-w-0 flex-1 text-sm">نام شهر · {{ $city->county->name }}
                            <input name="name" value="{{ $city->name }}" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-200">
                        </label>
                        <button class="rounded-lg bg-accent px-4 py-2 text-sm font-bold text-white">ذخیره نام</button>
                    </form>
                @empty
                    <p class="text-sm text-slate-500">هنوز شهری برای این استان ثبت نشده است.</p>
                @endforelse
                {{ $cities->links() }}
            </div>
        @endif
    </section>
</x-admin-layout>
