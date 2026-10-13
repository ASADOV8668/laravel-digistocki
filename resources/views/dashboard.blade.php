<x-app-layout title="داشبورد من">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold text-primary">حساب کاربری</p>
                <h1 class="mt-1 text-xl font-black text-neutral">داشبورد من</h1>
            </div>
            <span
                class="max-w-[140px] truncate rounded-full bg-primary/10 px-3 py-1.5 text-xs font-bold text-primary">{{ auth()->user()->name }}</span>
        </div>
    </x-slot>

    <section x-data="dashboardBulkActions(@js($visibleListingIds), @js($matchingListingIds))" class="space-y-6 px-4 py-6">
        @if (session('status'))
            <x-flowbite-alert type="success">{{ session('status') }}</x-flowbite-alert>
        @endif
        @if ($errors->any())
            <x-flowbite-alert type="danger">{{ $errors->first() }}</x-flowbite-alert>
        @endif
        @if ($dateRangeInvalid)
            <x-flowbite-alert type="warning">بازه تاریخ معتبر نیست؛ تاریخ شروع باید قبل از تاریخ پایان
                باشد.</x-flowbite-alert>
        @endif
        @unless ($hasListings)
            <div class="relative overflow-hidden rounded-lg bg-primary p-5 text-white shadow-lg shadow-primary/15">
                <div class="relative">
                    <p class="text-sm text-white/75">مدیریت آگهی‌های شما</p>
                    <h2 class="mt-1 text-2xl font-black">همه‌چیز مرتب و آماده فروش</h2>
                    <p class="mt-2 text-xs leading-6 text-white/75">وضعیت آگهی‌هایت را بررسی کن و سریع‌تر به خریدارها پاسخ
                        بده.</p><a href="{{ route('listings.create') }}"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-black text-primary transition hover:bg-primary-50"><x-heroicon-o-plus
                            class="h-5 w-5" />ثبت آگهی جدید</a>
                </div>
            </div>
        @endunless
        @if ($store)
            <div
                class="rounded-lg {{ $store->is_enabled ? 'bg-neutral text-white' : 'bg-slate-100 text-neutral' }} p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        @if ($store->logo_path)
                            <img src="{{ asset('storage/' . $store->logo_path) }}" alt="{{ $store->name }}"
                                class="h-11 w-11 rounded-lg object-cover ring-2 ring-white/20">
                        @else
                            <span
                                class="flex h-11 w-11 items-center justify-center rounded-lg {{ $store->is_enabled ? 'bg-primary text-white' : 'bg-warning/15 text-warning' }}"><x-heroicon-o-building-storefront
                                    class="h-6 w-6" /></span>
                        @endif
                        <div>
                            <p class="text-xs {{ $store->is_enabled ? 'text-white/60' : 'text-slate-500' }}">فروشگاه شما
                            </p>
                            <h2 class="mt-1 font-black">{{ $store->name }}</h2>
                        </div>
                    </div><span
                        class="rounded-full px-3 py-1.5 text-[10px] font-black {{ $store->is_admin_disabled ? 'bg-error/15 text-error' : ($store->isApproved() ? 'bg-success/15 text-success' : 'bg-warning/15 text-warning') }}">{{ $store->is_admin_disabled ? 'غیرفعال توسط مدیریت' : ($store->isApproved() ? ($store->is_enabled ? 'فعال و قابل مشاهده' : 'غیرفعال') : 'در انتظار تأیید مدیریت') }}</span>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($store->isPubliclyEnabled())
                        <a href="{{ route('storefront.show', $store) }}"
                            class="rounded-lg bg-white px-4 py-2.5 text-xs font-black text-neutral">مشاهده فروشگاه</a>
                        <div x-data="shareLink(@js(route('storefront.show', $store)))">
                            <button type="button" @click="share()"
                                class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-white/20">
                                <x-heroicon-o-share class="h-4 w-4" />
                                <span x-text="copied ? 'لینک کپی شد' : 'اشتراک‌گذاری فروشگاه'">اشتراک‌گذاری فروشگاه</span>
                            </button>
                        </div>
                    @endif
                    <a href="{{ route('storefront.edit') }}"
                        class="rounded-lg bg-white/10 px-4 py-2.5 text-xs font-bold text-white">
                        ویرایش اطلاعات</a>
                </div>
            </div>
        @else
            <a href="{{ route('storefront.edit') }}"
                class="flex items-center justify-between rounded-lg border border-dashed border-primary/30 bg-primary-50 p-5 text-primary transition hover:border-primary"><span><strong
                        class="block font-black">فروشگاه خودت را ثبت کن</strong><small
                        class="mt-1 block text-xs text-primary/70">آگهی‌هایت را در یک صفحه اختصاصی به خریدارها نمایش
                        بده.</small></span><x-heroicon-o-building-storefront class="h-7 w-7" /></a>
        @endif
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach (['pending' => ['در انتظار', 'text-warning', 'bg-warning/10'], 'approved' => ['تأییدشده', 'text-success', 'bg-success/10'], 'rejected' => ['ردشده', 'text-error', 'bg-error/10'], 'sold' => ['فروخته‌شده', 'text-info', 'bg-info/10'], 'expired' => ['منقضی‌شده', 'text-slate-500', 'bg-slate-100']] as $key => [$label, $color, $background])
                <a href="{{ route('dashboard', array_merge(request()->query(), ['status' => $key])) }}"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5"><span
                        class="flex h-9 w-9 items-center justify-center rounded-lg {{ $background }} {{ $color }}"><x-heroicon-o-chart-bar
                            class="h-5 w-5" /></span>
                    <p class="mt-3 text-xs font-bold text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black {{ $color }}">{{ number_format($counts->get($key, 0)) }}
                    </p>
                </a>
            @endforeach
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-neutral">آگهی‌های من</h2>
                <p class="mt-1 text-xs text-slate-400">جستجو، فیلتر تاریخ و مدیریت سریع آگهی‌ها.</p>
            </div><a href="{{ route('listings.index') }}" class="text-xs font-bold text-primary">جستجوی عمومی</a>
        </div>
        <form method="GET" action="{{ route('dashboard') }}"
            class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4">
            <input name="q" value="{{ $filters['q'] }}" placeholder="عنوان، مدل یا ویژگی"
                class="rounded-lg border-slate-200 text-sm focus:border-primary focus:ring-primary/30"><select
                name="brand_id" class="rounded-lg border-slate-200 text-sm">
                <option value="">همه برندها</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) $filters['brand_id'] === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <select name="phone_model_id" class="rounded-lg border-slate-200 text-sm">
                <option value="">همه مدل‌ها</option>
                @foreach ($modelBrands as $brand)
                    @foreach ($brand->phoneModels as $model)
                        <option value="{{ $model->id }}" @selected((string) $filters['phone_model_id'] === (string) $model->id)>
                            {{ $model->name_fa ?: $model->name }}</option>
                    @endforeach
                @endforeach
            </select>
            <x-dashboard-attribute-filters :filter-definitions="$filterAttributes" />
            <div class="rounded-lg border border-slate-100 bg-slate-50 p-2 sm:col-span-2 lg:col-span-2">
                <p class="mb-2 text-[11px] font-bold text-slate-500">از تاریخ</p><x-persian-date-select name="from_date"
                    :value="$filters['from_date']" :default-today="false" class="public-select" />
            </div>
            <div class="rounded-lg border border-slate-100 bg-slate-50 p-2 sm:col-span-2 lg:col-span-2">
                <p class="mb-2 text-[11px] font-bold text-slate-500">تا تاریخ</p><x-persian-date-select name="to_date"
                    :value="$filters['to_date']" :default-today="false" class="public-select" />
            </div>
            <div class="rounded-lg border border-slate-100 bg-slate-50 p-2">
                <label for="dashboard-per-page" class="mb-2 block text-[11px] font-bold text-slate-500">تعداد نمایش در صفحه</label>
                <select id="dashboard-per-page" name="per_page" onchange="this.form.submit()"
                    class="w-full rounded-lg border-slate-200 bg-white text-sm focus:border-primary focus:ring-primary/30">
                    @foreach ([10, 25, 50, 100, 250, 500] as $pageSize)
                        <option value="{{ $pageSize }}" @selected($perPage === $pageSize)>{{ \App\Support\PersianNumber::digits($pageSize) }} آگهی</option>
                    @endforeach
                </select>
            </div><button
                class="rounded-lg bg-neutral px-4 py-3 text-sm font-bold text-white sm:col-span-2 lg:col-span-4">اعمال
                فیلتر</button>
        </form>
        <div role="tablist" aria-label="فیلتر وضعیت آگهی‌ها"
            class="flex gap-2 overflow-x-auto rounded-lg border border-slate-200 bg-white p-2 text-xs font-bold whitespace-nowrap shadow-sm">
            <a role="tab" aria-selected="{{ !$status ? 'true' : 'false' }}"
                href="{{ route('dashboard', request()->except('status')) }}"
                class="rounded-lg px-3 py-2 focus:outline-none focus:ring-4 focus:ring-primary/20 {{ !$status ? 'bg-neutral text-white' : 'text-slate-500 hover:bg-slate-50' }}">همه</a>
            @foreach (['pending' => 'در انتظار', 'approved' => 'تأییدشده', 'rejected' => 'ردشده', 'sold' => 'فروخته‌شده', 'expired' => 'منقضی‌شده'] as $key => $label)
                <a role="tab" aria-selected="{{ $status === $key ? 'true' : 'false' }}"
                    href="{{ route('dashboard', array_merge(request()->query(), ['status' => $key])) }}"
                    class="rounded-lg px-3 py-2 focus:outline-none focus:ring-4 focus:ring-primary/20 {{ $status === $key ? 'bg-neutral text-white' : 'text-slate-500 hover:bg-slate-50' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form id="bulk-listings-form" method="POST" action="{{ route('dashboard.listings.bulk') }}"
            @submit="if (!selectedIds.length) { $event.preventDefault(); } else if ($refs.bulkAction.value === 'delete' && !window.confirm('آگهی‌های انتخاب‌شده حذف شوند؟ این عملیات قابل بازگشت نیست.')) { $event.preventDefault(); }"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-primary/15 bg-primary-50 p-3">@csrf<span
                class="text-xs font-bold text-primary">عملیات روی انتخاب‌ها:</span><select x-ref="bulkAction"
                name="action" class="rounded-lg border-primary/20 bg-white py-2 text-xs">
                <option value="disable">غیرفعال‌کردن</option>
                <option value="enable">فعال‌کردن</option>
                <option value="sold">فروخته‌شده</option>
                <option value="contact_price">حذف قیمت و تماس بگیرید</option>
                <option value="delete">حذف آگهی</option>
            </select><template x-for="id in selectedOutsidePage()" :key="`bulk-hidden-${id}`"><input type="hidden"
                    name="listing_ids[]" :value="id"></template><button type="submit"
                :disabled="selectedIds.length === 0"
                class="rounded-lg bg-primary px-3 py-2 text-xs font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">اجرا</button><button
                type="button" data-modal-target="bulk-price-modal" data-modal-toggle="bulk-price-modal"
                :disabled="selectedIds.length === 0"
                class="rounded-lg bg-info px-3 py-2 text-xs font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">تغییر
                قیمت گروهی</button><label
                class="inline-flex items-center gap-2 text-[11px] font-bold text-primary"><input type="checkbox"
                    @change="toggleVisible($event)" :checked="isVisibleSelected() && !allMatching"
                    class="public-check"> انتخاب همه این صفحه</label>
            @if (count($matchingListingIds) > count($visibleListingIds))
                <button type="button" @click="allMatching ? clearSelection() : selectAllMatching()"
                    class="rounded-lg bg-white px-3 py-2 text-[11px] font-bold text-primary ring-1 ring-primary/20"><span
                        x-text="allMatching ? 'لغو انتخاب همه نتایج' : 'انتخاب همه نتایج فیلترشده ('.concat('{{ \App\Support\PersianNumber::digits(count($matchingListingIds)) }}', ' مورد)' )">انتخاب همه نتایج فیلترشده ({{ \App\Support\PersianNumber::digits(count($matchingListingIds)) }} مورد)</span></button>
            @endif
            <span class="w-full text-[11px] text-primary/70"
                x-text="selectedIds.length ? `${selectedIds.length} آگهی انتخاب شده` : 'برای انتخاب همه موارد، مربع هر آگهی را بزنید.'">
            </span>
        </form>
        <div id="dashboard-listings" role="tabpanel" aria-label="فهرست آگهی‌های من" class="space-y-3">
            @forelse ($listings as $listing)
                <article data-listing-card="{{ $listing->id }}"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex gap-3"><label class="pt-1"><input type="checkbox" name="listing_ids[]"
                                value="{{ $listing->id }}" form="bulk-listings-form" x-model="selectedIds"
                                class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary/30"><span
                                class="sr-only">انتخاب {{ $listing->title }}</span></label>
                        @if ($listing->primaryImage)
                            <img src="{{ asset('storage/' . ($listing->primaryImage->thumbnail_path ?: $listing->primaryImage->path)) }}"
                                alt="{{ $listing->title }}" class="h-20 w-20 shrink-0 rounded-lg object-cover"
                            loading="lazy" decoding="async">@else<img
                                src="{{ asset('images/listing-placeholder.svg') }}" alt="تصویر پیش‌فرض آگهی"
                                class="h-20 w-20 shrink-0 rounded-lg object-cover" loading="lazy" decoding="async">
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h2 class="truncate font-black text-neutral">{{ $listing->title }}</h2><span
                                    class="shrink-0 rounded-full px-2 py-1 text-[10px] font-bold {{ $listing->is_user_disabled ? 'bg-slate-200 text-slate-500' : 'bg-slate-100 text-slate-600' }}">{{ $listing->is_user_disabled ? 'غیرفعال' : $listing->status->label() }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ $listing->brand->name }} ·
                                {{ $listing->phoneModel->name }}</p>
                            <p data-listing-price class="mt-2 text-xs font-black text-primary">
                                {{ $listing->price_on_request ? 'تماس بگیرید' : \App\Support\PersianNumber::format($listing->price) . ' تومان' }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2"><button type="button"
                                    data-modal-target="listing-price-modal-{{ $listing->id }}"
                                    data-modal-toggle="listing-price-modal-{{ $listing->id }}"
                                    class="rounded-lg bg-primary/10 px-3 py-2 text-xs font-bold text-primary">تغییر
                                    قیمت</button>
                                <form method="POST" action="{{ route('listings.toggle-disabled', $listing) }}">@csrf
                                    @method('PATCH')<button
                                        class="rounded-lg bg-warning/10 px-3 py-2 text-xs font-bold text-warning">{{ $listing->is_user_disabled ? 'فعال‌سازی' : 'غیرفعال‌کردن' }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (in_array($listing->status->value, ['pending', 'rejected', 'approved'], true))
                            <a href="{{ route('listings.edit', $listing) }}"
                                class="rounded-lg bg-primary/10 px-3 py-2 text-xs font-bold text-primary">{{ $store?->allowsFreeListing() ? 'ویرایش آگهی' : 'ویرایش کامل' }}</a>
                        @endif
                        <a href="{{ route('listings.create', ['clone' => $listing->id]) }}"
                            class="rounded-lg bg-info/10 px-3 py-2 text-xs font-bold text-info">
                            کپی آگهی</a>
                        @if ($listing->status->value === 'expired')
                            <form method="POST" action="{{ route('listings.renew', $listing) }}">@csrf
                                @method('PATCH')<button
                                    class="rounded-lg bg-primary/10 px-3 py-2 text-xs font-bold text-primary">ارسال
                                    مجدد</button></form>
                            @endif @if ($listing->status->value === 'approved')
                                <form method="POST" data-listing-status-form action="{{ route('listings.sold', $listing) }}"
                                    data-restore-action="{{ route('listings.restore-sold', $listing) }}">@csrf
                                    @method('PATCH')<button
                                        class="rounded-lg bg-success/10 px-3 py-2 text-xs font-bold text-success">فروخته
                                        شد</button></form>
                            @elseif ($listing->status->value === 'sold' && auth()->user()->can('restoreSold', $listing))
                                <form method="POST" data-listing-status-form action="{{ route('listings.restore-sold', $listing) }}">@csrf
                                    @method('PATCH')<button
                                        class="rounded-lg bg-success/10 px-3 py-2 text-xs font-bold text-success">بازگردانی به منتشرشده</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('listings.destroy', $listing) }}"
                                onsubmit="return confirm('آگهی حذف شود؟')">
                                @csrf @method('DELETE')<button
                                    class="rounded-lg bg-error/10 px-3 py-2 text-xs font-bold text-error">حذف</button>
                            </form>
                    </div>
                </article>
                <x-modal name="listing-price-modal-{{ $listing->id }}" maxWidth="md">
                    <div class="p-6">
                        <p class="text-xs font-bold text-primary">ویرایش سریع آگهی</p>
                        <h3 class="mt-1 text-lg font-black text-neutral">{{ $listing->title }}</h3>
                        <form method="POST" action="{{ route('listings.quick-update', $listing) }}"
                            data-quick-update-listing="{{ $listing->id }}"
                            class="mt-5 space-y-4">@csrf @method('PATCH')<div
                                x-data="{ priceOnRequest: @js((bool) old('price_on_request', $listing->price_on_request)) }"><label
                                    for="quick-price-{{ $listing->id }}"
                                    class="mb-2 block text-sm font-bold text-neutral">قیمت (تومان)</label>
                                <div x-data="moneyInput(@js(old('price', $listing->price ?? '')))"
                                    x-show="!priceOnRequest">
                                    <input id="quick-price-{{ $listing->id }}" x-model="display"
                                        @input="formatInput($event)" :required="!priceOnRequest" type="text"
                                        inputmode="numeric" autocomplete="off" dir="ltr"
                                        placeholder="مثلاً ۳۲٬۰۰۰٬۰۰۰"
                                        class="w-full rounded-lg border-slate-200 text-left text-sm focus:border-primary focus:ring-primary/30">
                                    <input type="hidden" name="price" :value="raw">
                                </div>
                            </div>
                            @if ($allowContactPrice)
                                <label class="flex items-center gap-2 text-sm font-bold text-neutral"><input
                                        type="checkbox" name="price_on_request" value="1"
                                        x-model="priceOnRequest" @checked($listing->price_on_request)
                                        class="public-check"> قیمت نمایش داده نشود؛ تماس
                                    بگیرید</label>
                            @endif
                            <div class="flex justify-end gap-2"><button type="button"
                                    data-modal-hide="listing-price-modal-{{ $listing->id }}"
                                    class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-600">انصراف</button><button
                                    type="submit" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-bold text-white">ذخیره
                                    قیمت</button></div>
                        </form>
                    </div>
                </x-modal>
            @empty
                <div class="rounded-lg border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm">
                    <x-heroicon-o-rectangle-stack class="mx-auto h-12 w-12 text-primary/30" />
                    <h3 class="mt-4 font-black text-neutral">در این وضعیت آگهی‌ای ندارید</h3>
                    <p class="mt-2 text-xs leading-6 text-slate-500">اولین آگهی خودت را ثبت کن و آن را از همین‌جا
                        مدیریت کن.</p><a href="{{ route('listings.create') }}"
                        class="mt-4 inline-flex rounded-lg bg-primary px-4 py-2.5 text-xs font-bold text-white">ثبت
                        آگهی</a>
                </div>
            @endforelse
        </div>
        {{ $listings->links('vendor.pagination.flowbite') }}
        <x-modal name="bulk-price-modal" maxWidth="md">
            <div class="p-6">
                <p class="text-xs font-bold text-info">عملیات گروهی</p>
                <h3 class="mt-1 text-lg font-black text-neutral">تغییر قیمت آگهی‌های انتخاب‌شده</h3>
                <p class="mt-2 text-xs leading-6 text-slate-500">این قیمت روی <span class="font-black text-primary"
                        x-text="selectedIds.length"></span> آگهی انتخاب‌شده اعمال می‌شود.</p>
                <form method="POST" action="{{ route('dashboard.listings.bulk') }}" class="mt-5 space-y-4">
                    @csrf<input type="hidden" name="action" value="price"><template x-for="id in selectedIds"
                        :key="`bulk-price-${id}`"><input type="hidden" name="listing_ids[]"
                            :value="id"></template>
                    <div x-data="moneyInput('')"><label for="bulk-price"
                            class="mb-2 block text-sm font-bold text-neutral">قیمت جدید (تومان)</label><input
                            id="bulk-price" x-model="display" @input="formatInput($event)" type="text"
                            inputmode="numeric" autocomplete="off" dir="ltr" placeholder="مثلاً ۳۲٬۰۰۰٬۰۰۰"
                            required
                            class="w-full rounded-lg border-slate-200 text-left text-sm focus:border-primary focus:ring-primary/30"><input
                            type="hidden" name="bulk_price" :value="raw">
                    </div>
                    <div class="flex justify-end gap-2"><button type="button" data-modal-hide="bulk-price-modal"
                            class="rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-600">انصراف</button><button
                            class="rounded-lg bg-info px-4 py-2.5 text-sm font-bold text-white">اعمال قیمت</button>
                    </div>
                </form>
            </div>
        </x-modal>
    </section>
</x-app-layout>
