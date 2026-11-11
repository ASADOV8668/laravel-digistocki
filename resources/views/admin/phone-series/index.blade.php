<x-admin-layout title="سری‌های گوشی">
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-black text-slate-900">سری‌های گوشی</h2>
            <p class="mt-1 text-xs text-slate-400">سری‌ها را برای کوتاه‌کردن مسیر انتخاب مدل مدیریت کنید.</p>
        </div>
    </x-slot>
    <section class="space-y-6">
        <x-admin.feedback />
        <x-admin.catalog-tabs active="series" />
        <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
            <form method="POST" action="{{ route('admin.phone-series.store') }}" class="admin-card h-fit space-y-4 p-5">
                @csrf
                <h3 class="text-sm font-black text-slate-900">افزودن سری</h3>
                <select name="brand_id" required class="w-full rounded-xl border-slate-200 text-sm">
                    <option value="">برند را انتخاب کنید</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }} / {{ $brand->name_en }}</option>
                    @endforeach
                </select>
                <input name="name" required placeholder="نام داخلی، مثل A یا iPhone 13" class="w-full rounded-xl border-slate-200 text-sm">
                <input name="name_fa" placeholder="نام فارسی" class="w-full rounded-xl border-slate-200 text-sm">
                <input name="name_en" placeholder="نام انگلیسی" dir="ltr" class="w-full rounded-xl border-slate-200 text-sm">
                <input name="sort_order" type="number" min="0" value="0" placeholder="اولویت" class="w-full rounded-xl border-slate-200 text-sm">
                <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white hover:bg-accent">ذخیره سری</button>
            </form>
            <div class="admin-card overflow-hidden">
                <div class="border-b border-slate-100 p-5">
                    <h3 class="text-sm font-black text-slate-900">فهرست سری‌ها</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($series as $item)
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="font-black text-slate-800">{{ $item->brand->name }} · {{ $item->name_fa ?: $item->name }}</h2>
                                    <p class="mt-1 text-xs text-slate-400">{{ $item->name_en ?: $item->name }} · {{ number_format($item->phone_models_count) }} مدل</p>
                                </div>
                                <form method="POST" action="{{ route('admin.phone-series.toggle', $item) }}">
                                    @csrf @method('PATCH')
                                    <button class="rounded-full px-3 py-1.5 text-[11px] font-black {{ $item->is_active ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500' }}">{{ $item->is_active ? 'فعال' : 'غیرفعال' }}</button>
                                </form>
                            </div>
                            <details class="mt-4">
                                <summary class="cursor-pointer text-xs font-bold text-accent">ویرایش سری</summary>
                                <form method="POST" action="{{ route('admin.phone-series.update', $item) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @csrf @method('PATCH')
                                    <select name="brand_id" required class="rounded-lg border-slate-200 text-sm">
                                        @foreach ($brands as $brand)<option value="{{ $brand->id }}" @selected($item->brand_id === $brand->id)>{{ $brand->name }}</option>@endforeach
                                    </select>
                                    <input name="sort_order" type="number" min="0" value="{{ $item->sort_order }}" class="rounded-lg border-slate-200 text-sm">
                                    <input name="name" value="{{ $item->name }}" required class="rounded-lg border-slate-200 text-sm">
                                    <input name="name_fa" value="{{ $item->name_fa }}" class="rounded-lg border-slate-200 text-sm">
                                    <input name="name_en" value="{{ $item->name_en }}" dir="ltr" class="rounded-lg border-slate-200 text-sm sm:col-span-2">
                                    <button class="rounded-lg bg-accent px-3 py-2 text-xs font-bold text-white sm:col-span-2">ذخیره تغییرات</button>
                                </form>
                            </details>
                        </div>
                    @empty
                        <div class="p-10 text-center text-sm text-slate-400">هنوز سری‌ای ثبت نشده است.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <x-admin.pagination :paginator="$series" route="admin.phone-series.index" :per-page-options="$perPageOptions" />
    </section>
</x-admin-layout>
