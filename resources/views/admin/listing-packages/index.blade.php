<x-admin-layout title="بسته‌ها و سهمیهٔ آگهی">
    <x-slot name="header">
        <h1 class="text-xl font-black">بسته‌ها و سهمیهٔ آگهی</h1>
    </x-slot>

    <div class="space-y-5">
        <p class="text-sm text-slate-600 dark:text-slate-300">در این مرحله، بسته و سهمیه برای فروشگاه ثبت می‌شود. اعمال سقف هنگام انتشار و محاسبهٔ سهمیهٔ مصرف‌شده در مرحلهٔ بعد فعال می‌شود.</p>
        @if (session('status'))
            <p role="status" class="rounded-xl bg-green-50 p-3 text-green-800">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul role="alert" class="list-inside list-disc rounded-xl bg-red-50 p-3 text-red-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <div class="grid gap-5 lg:grid-cols-2">
            <form method="POST" action="{{ route('admin.listing-packages.store') }}" class="admin-card space-y-4 p-5">
                @csrf
                <h2 class="font-bold">تعریف بسته</h2>
                <label class="block text-sm">نام بسته
                    <input name="name" value="{{ old('name') }}" required maxlength="120" class="public-input mt-1 w-full">
                </label>
                @foreach (['publication_quota' => ['سهمیهٔ کل انتشار', 1000000], 'active_listing_limit' => ['سقف آگهی فعال هم‌زمان', 1000000], 'duration_days' => ['مدت اعتبار به روز', 3650]] as $name => [$label, $max])
                    <label class="block text-sm">{{ $label }}
                        <input name="{{ $name }}" value="{{ old($name) }}" type="number" min="1" max="{{ $max }}" required dir="ltr" class="public-input mt-1 w-full">
                    </label>
                @endforeach
                <button type="submit" class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white">ثبت بسته</button>
            </form>

            <form method="POST" action="{{ route('admin.listing-packages.assign') }}" class="admin-card space-y-4 p-5">
                @csrf
                <h2 class="font-bold">تخصیص بسته به فروشگاه</h2>
                <label class="block text-sm">فروشگاه
                    <select name="seller_store_id" required class="public-input mt-1 w-full">
                        <option value="">انتخاب فروشگاه</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected(old('seller_store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">بسته
                    <select name="listing_package_id" required class="public-input mt-1 w-full">
                        <option value="">انتخاب بسته</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected(old('listing_package_id') == $package->id)>{{ $package->name }} — {{ $package->publication_quota }} انتشار، {{ $package->active_listing_limit }} فعال، {{ $package->duration_days }} روز</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" @disabled($packages->isEmpty() || $stores->isEmpty()) class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white disabled:opacity-50">تخصیص بسته</button>
            </form>
        </div>

        <section class="admin-card space-y-4 p-5">
            <h2 class="font-bold">بسته‌های تخصیص‌یافته</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-right text-sm [&_td]:p-2 [&_th]:p-2">
                    <thead><tr><th>فروشگاه</th><th>بسته</th><th>سهمیهٔ انتشار</th><th>سقف فعال</th><th>شروع</th><th>پایان</th></tr></thead>
                    <tbody>
                        @forelse ($assignments as $assignment)
                            <tr class="border-t border-slate-200 dark:border-slate-700">
                                <td>{{ $assignment->store_name }}</td><td>{{ $assignment->package_name }}</td>
                                <td>{{ $assignment->publication_quota }}</td><td>{{ $assignment->active_listing_limit }}</td>
                                <td>{{ \App\Support\PersianDate::format($assignment->starts_at) }}</td>
                                <td>{{ \App\Support\PersianDate::format($assignment->ends_at) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">هنوز بسته‌ای تخصیص نیافته است.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $assignments->links() }}
        </section>
    </div>
</x-admin-layout>
