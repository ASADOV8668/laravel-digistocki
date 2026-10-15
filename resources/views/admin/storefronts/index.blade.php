<x-admin-layout title="مدیریت فروشگاه‌ها">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-heroicon-o-building-storefront
                        class="h-6 w-6" /></span>
                <div>
                    <h2 class="text-xl font-black text-slate-900">مدیریت فروشگاه‌ها</h2>
                    <p class="mt-1 text-xs text-slate-400">درخواست فروشگاه کاربران را بررسی و مدیریت کنید.</p>
                </div>
            </div>
            <a href="{{ route('admin.storefronts.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-black text-white transition hover:bg-primary"><x-heroicon-o-users
                    class="h-5 w-5" />ایجاد فروشگاه برای کاربر</a>
        </div>
    </x-slot>

    <section class="space-y-6">
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <a href="{{ route('admin.storefronts.index') }}" class="admin-card p-4 transition hover:-translate-y-0.5">
                <p class="text-xs font-bold text-slate-500">کل فروشگاه‌ها</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format($storeStats['total']) }}</p>
            </a>
            <a href="{{ route('admin.storefronts.index', ['status' => 'active']) }}"
                class="admin-card p-4 transition hover:-translate-y-0.5">
                <p class="text-xs font-bold text-success">فعال و قابل مشاهده</p>
                <p class="mt-2 text-2xl font-black text-success">{{ number_format($storeStats['active']) }}</p>
            </a>
            <a href="{{ route('admin.storefronts.index', ['status' => 'disabled']) }}"
                class="admin-card p-4 transition hover:-translate-y-0.5">
                <p class="text-xs font-bold text-slate-500">غیرفعال توسط کاربر</p>
                <p class="mt-2 text-2xl font-black text-slate-500">{{ number_format($storeStats['disabled']) }}</p>
            </a>
            <a href="{{ route('admin.storefronts.index', ['approval_status' => 'pending']) }}"
                class="admin-card p-4 transition hover:-translate-y-0.5">
                <p class="text-xs font-bold text-warning">در انتظار بررسی</p>
                <p class="mt-2 text-2xl font-black text-warning">{{ number_format($storeStats['pending']) }}</p>
            </a>
            <a href="{{ route('admin.storefronts.index', ['status' => 'blocked']) }}"
                class="admin-card p-4 transition hover:-translate-y-0.5">
                <p class="text-xs font-bold text-error">مسدود توسط مدیریت</p>
                <p class="mt-2 text-2xl font-black text-error">{{ number_format($storeStats['blocked']) }}</p>
            </a>
        </div>
        <div class="admin-card p-5">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row">
                <input name="q" value="{{ request('q') }}" placeholder="نام فروشگاه، slug، نام یا موبایل کاربر"
                    class="min-w-0 flex-1 rounded-xl border-slate-200 py-3 text-sm focus:border-primary focus:ring-primary">
                <select name="approval_status"
                    class="rounded-xl border-slate-200 py-3 text-sm focus:border-primary focus:ring-primary">
                    <option value="">همه درخواست‌ها</option>
                    <option value="pending" @selected(request('approval_status') === 'pending')>در انتظار بررسی</option>
                    <option value="rejected" @selected(request('approval_status') === 'rejected')>ردشده</option>
                </select>
                <button class="rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white">جستجو</button>
                @if (request()->hasAny(['q', 'status', 'approval_status']))
                    <a href="{{ route('admin.storefronts.index') }}"
                        class="rounded-xl bg-slate-100 px-5 py-3 text-center text-sm font-bold text-slate-600">پاک‌کردن</a>
                @endif
            </form>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="divide-y divide-slate-100">
                @forelse ($stores as $store)
                    <article
                        class="flex flex-col gap-4 p-5 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($store->logo_path)
                                <img src="{{ asset('storage/' . $store->logo_path) }}" alt="{{ $store->name }}"
                                class="h-12 w-12 rounded-2xl object-cover">@else<span
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 font-black text-primary">{{ mb_substr($store->name, 0, 1) }}</span>
                            @endif
                            <div class="min-w-0">
                                <h3 class="truncate font-black text-slate-800">{{ $store->name }}</h3>
                                <a href="{{ route('admin.storefronts.show', $store) }}"
                                    class="mt-1 inline-flex items-center gap-1 text-sm font-black text-primary hover:text-primary-700">
                                    <x-heroicon-o-user-circle class="h-4 w-4" />{{ $store->user->name }}
                                </a>
                                <p class="mt-1 truncate text-xs text-slate-400" dir="ltr">
                                    /store/{{ $store->slug }} · {{ $store->user->mobile }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ collect([$store->province?->name, $store->city?->name])->filter()->implode('، ') ?:'موقعیت ثبت نشده' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full px-3 py-1.5 text-[10px] font-black {{ $store->is_admin_disabled ? 'bg-error/10 text-error' : ($store->isPending() ? 'bg-warning/10 text-warning' : ($store->isRejected() ? 'bg-error/10 text-error' : ($store->is_enabled ? 'bg-success/10 text-success' : 'bg-slate-100 text-slate-500'))) }}">{{ $store->is_admin_disabled ? 'مسدود توسط مدیریت' : ($store->isPending() ? 'در انتظار بررسی' : ($store->isRejected() ? 'ردشده' : ($store->is_enabled ? 'فعال' : 'غیرفعال'))) }}</span>
                            @if ($store->isPending())
                                <form method="POST" action="{{ route('admin.storefronts.approve', $store) }}">@csrf
                                    @method('PATCH')<button
                                        class="rounded-xl bg-success/10 px-3 py-2 text-xs font-bold text-success">تأیید</button>
                                </form>
                                <form x-data="{ open: false }" method="POST"
                                    action="{{ route('admin.storefronts.reject', $store) }}"
                                    class="flex items-center gap-1">@csrf @method('PATCH')<button type="button"
                                        @click="open = !open"
                                        class="rounded-xl bg-error/10 px-3 py-2 text-xs font-bold text-error">رد
                                        درخواست</button><template x-if="open"><span
                                            class="flex items-center gap-1"><input name="rejection_reason" required
                                                maxlength="1000" placeholder="علت رد درخواست"
                                                class="w-44 rounded-xl border-error/20 px-2 py-2 text-xs"><button
                                                class="rounded-xl bg-error px-3 py-2 text-xs font-bold text-white">ثبت</button></span></template>
                                </form>
                            @endif
                            @if ($store->isPubliclyEnabled())
                                <a href="{{ route('storefront.show', $store) }}" target="_blank"
                                    class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600">نمایش</a>
                            @endif
                            @if ($store->isRejected())
                                @if (($store->user->listings_count ?? 0) === 0)
                                    <form method="POST" action="{{ route('admin.storefronts.destroy', $store) }}"
                                        onsubmit="return confirm('فروشگاه ردشده و تصاویر آن به‌طور کامل حذف شود؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="inline-flex items-center gap-1 rounded-xl bg-error/10 px-3 py-2 text-xs font-bold text-error">
                                            <x-heroicon-o-trash class="h-4 w-4" />حذف کامل
                                        </button>
                                    </form>
                                @else
                                    <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-400"
                                        title="این فروشگاه آگهی دارد و قابل حذف نیست">دارای آگهی؛ حذف نمی‌شود</span>
                                @endif
                            @endif
                            <form method="POST" action="{{ route('admin.storefronts.toggle', $store) }}">@csrf
                                @method('PATCH')<button
                                    class="rounded-xl {{ $store->is_admin_disabled ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning' }} px-3 py-2 text-xs font-bold">{{ $store->is_admin_disabled ? 'رفع مسدودی' : 'غیرفعال‌سازی مدیریتی' }}</button>
                            </form>
                            <a href="{{ route('admin.storefronts.edit', $store->user) }}"
                                class="rounded-xl bg-primary/10 px-3 py-2 text-xs font-bold text-primary">ویرایش</a>
                        </div>
                    </article>
                @empty
                    <div class="p-12 text-center text-sm text-slate-400">هنوز فروشگاهی ثبت نشده است.</div>
                @endforelse
            </div>
        </div>
        <x-admin.pagination :paginator="$stores" route="admin.storefronts.index" :per-page-options="$perPageOptions" />
    </section>
</x-admin-layout>
