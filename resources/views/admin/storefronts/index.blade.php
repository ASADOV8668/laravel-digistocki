<x-admin-layout title="مدیریت غرفه‌ها">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-heroicon-o-building-storefront class="h-6 w-6" /></span>
                <div><h2 class="text-xl font-black text-slate-900">مدیریت غرفه‌ها</h2><p class="mt-1 text-xs text-slate-400">ویترین عمومی کاربران را مدیریت کنید.</p></div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-black text-white transition hover:bg-primary"><x-heroicon-o-users class="h-5 w-5" />انتخاب از کاربران</a>
        </div>
    </x-slot>

    <section class="space-y-6">
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <a href="{{ route('admin.storefronts.index') }}" class="admin-card p-4 transition hover:-translate-y-0.5"><p class="text-xs font-bold text-slate-500">کل غرفه‌ها</p><p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($storeStats['total']) }}</p></a>
            <a href="{{ route('admin.storefronts.index', ['status' => 'active']) }}" class="admin-card p-4 transition hover:-translate-y-0.5"><p class="text-xs font-bold text-success">فعال و قابل مشاهده</p><p class="mt-2 text-2xl font-black text-success">{{ number_format($storeStats['active']) }}</p></a>
            <a href="{{ route('admin.storefronts.index', ['status' => 'disabled']) }}" class="admin-card p-4 transition hover:-translate-y-0.5"><p class="text-xs font-bold text-slate-500">غیرفعال توسط کاربر</p><p class="mt-2 text-2xl font-black text-slate-500">{{ number_format($storeStats['disabled']) }}</p></a>
            <a href="{{ route('admin.storefronts.index', ['status' => 'blocked']) }}" class="admin-card p-4 transition hover:-translate-y-0.5"><p class="text-xs font-bold text-error">مسدود توسط مدیریت</p><p class="mt-2 text-2xl font-black text-error">{{ number_format($storeStats['blocked']) }}</p></a>
        </div>
        <div class="admin-card p-5">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row">
                <input name="q" value="{{ request('q') }}" placeholder="نام غرفه، slug، نام یا موبایل کاربر" class="min-w-0 flex-1 rounded-xl border-slate-200 py-3 text-sm focus:border-primary focus:ring-primary">
                <select name="status" class="rounded-xl border-slate-200 py-3 text-sm focus:border-primary focus:ring-primary"><option value="">همه وضعیت‌ها</option><option value="active" @selected(request('status') === 'active')>فعال و قابل مشاهده</option><option value="disabled" @selected(request('status') === 'disabled')>غیرفعال توسط کاربر</option><option value="blocked" @selected(request('status') === 'blocked')>مسدود توسط مدیریت</option></select>
                <button class="rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white">جستجو</button>
                @if (request()->hasAny(['q', 'status']))<a href="{{ route('admin.storefronts.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-600">پاک‌کردن</a>@endif
            </form>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="divide-y divide-slate-100">
                @forelse ($stores as $store)
                    <article class="flex flex-col gap-4 p-5 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($store->logo_path)<img src="{{ asset('storage/'.$store->logo_path) }}" alt="{{ $store->name }}" class="h-12 w-12 rounded-2xl object-cover">@else<span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 font-black text-primary">{{ mb_substr($store->name, 0, 1) }}</span>@endif
                            <div class="min-w-0">
                                <h3 class="truncate font-black text-slate-800">{{ $store->name }}</h3>
                                <p class="mt-1 truncate text-xs text-slate-400" dir="ltr">/store/{{ $store->slug }} · {{ $store->user->mobile }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ collect([$store->province?->name, $store->city?->name])->filter()->implode('، ') ?: 'موقعیت ثبت نشده' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-3 py-1.5 text-[10px] font-black {{ $store->is_admin_disabled ? 'bg-error/10 text-error' : ($store->is_enabled ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500') }}">{{ $store->is_admin_disabled ? 'مسدود توسط مدیریت' : ($store->is_enabled ? 'فعال' : 'غیرفعال') }}</span>
                            @if ($store->isPubliclyEnabled())<a href="{{ route('storefront.show', $store) }}" target="_blank" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600">نمایش</a>@endif
                            <form method="POST" action="{{ route('admin.storefronts.toggle', $store) }}">@csrf @method('PATCH')<button class="rounded-xl {{ $store->is_admin_disabled ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }} px-3 py-2 text-xs font-bold">{{ $store->is_admin_disabled ? 'رفع مسدودی' : 'غیرفعال‌سازی مدیریتی' }}</button></form>
                            <a href="{{ route('admin.storefronts.edit', $store->user) }}" class="rounded-xl bg-primary/10 px-3 py-2 text-xs font-bold text-primary">ویرایش</a>
                        </div>
                    </article>
                @empty
                    <div class="p-12 text-center text-sm text-slate-400">هنوز غرفه‌ای ثبت نشده است.</div>
                @endforelse
            </div>
        </div>
        {{ $stores->links() }}
    </section>
</x-admin-layout>
