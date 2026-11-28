<x-admin-layout title="پیش‌نویس سفارش بسته">
    <x-slot name="header">
        <h1 class="text-xl font-black">پیش‌نویس سفارش بسته</h1>
    </x-slot>

    <form method="POST" action="{{ route('admin.finance.orders.store') }}" class="admin-card space-y-4 p-5">
        @csrf
        <p class="text-sm">پیش‌نویس فقط برای بررسی مدیر ثبت می‌شود. مبلغی دریافت نمی‌شود و بسته‌ای فعال نمی‌شود.</p>
        @if (session('status'))
            <p role="status" class="text-sm text-success">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul role="alert" class="list-inside list-disc text-sm text-error">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <div class="grid gap-4 lg:grid-cols-3">
            <label class="block text-sm">فروشگاه
                <select name="seller_store_id" required class="public-input mt-1 w-full">
                    <option value="">انتخاب فروشگاه</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(old('seller_store_id') == $store->id)>{{ $store->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">بستهٔ آگهی
                <select name="listing_package_id" required class="public-input mt-1 w-full">
                    <option value="">انتخاب بسته</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected(old('listing_package_id') == $package->id)>{{ $package->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">مبلغ پیشنهادی به تومان
                <input name="amount_toman" value="{{ old('amount_toman') }}" type="number" min="0" max="1000000000000" dir="ltr" required class="public-input mt-1 w-full">
            </label>
        </div>
        <button type="submit" @disabled($packages->isEmpty() || $stores->isEmpty()) class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white disabled:opacity-50">ثبت پیش‌نویس</button>
    </form>

    <section class="admin-card mt-5 space-y-4 p-5">
        <h2 class="font-bold">فهرست سفارش‌های بسته</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-right text-sm [&_td]:p-2 [&_th]:p-2">
                <thead><tr><th>شناسه</th><th>فروشگاه</th><th>بسته</th><th>مبلغ پیشنهادی</th><th>تاریخ ثبت</th><th>وضعیت</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-t border-slate-200 dark:border-slate-700">
                            <td dir="ltr">{{ $order->reference }}</td>
                            <td>{{ $order->store_name }}</td>
                            <td>{{ $order->package_name }}</td>
                            <td>{{ number_format($order->amount_toman) }} تومان</td>
                            <td>{{ \App\Support\PersianDate::format($order->created_at) }}</td>
                            <td>{{ $order->status === 'draft' ? 'پیش‌نویس' : $order->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">هنوز سفارشی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </section>
</x-admin-layout>
