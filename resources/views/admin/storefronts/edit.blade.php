@php
    $storeExpiry = $store?->business_license_expires_at
        ? \App\Support\PersianDate::format($store->business_license_expires_at)
        : '';
    $defaultWhatsapp = $store?->whatsapp_url
        ?: \App\Support\SocialHandle::fromMobile($store?->contact_phone ?? $user->mobile);
@endphp
<x-admin-layout :title="'فروشگاه ' . $user->name">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.storefronts.index') }}"
                class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-100"
                aria-label="بازگشت"><x-heroicon-o-arrow-right class="h-5 w-5" /></a>
            <div>
                <p class="text-xs font-bold text-primary">مدیریت فروشگاه</p>
                <h2 class="mt-1 text-xl font-black text-slate-900">{{ $user->name }}</h2>
                <p class="mt-1 text-xs text-slate-400">{{ $user->mobile }}</p>
            </div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-4xl space-y-5">
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
            <h3 class="mt-1 text-lg font-black">{{ $store ? 'ویرایش فروشگاه کاربر' : 'ایجاد فروشگاه برای کاربر' }}</h3>
            <p class="mt-2 text-xs leading-6 text-white/70">مدیریت می‌تواند وضعیت تأیید، آدرس انگلیسی و اولویت نمایش
                فروشگاه را تغییر دهد.</p>
        </div>
        <form method="POST" action="{{ route('admin.storefronts.update', $user) }}" enctype="multipart/form-data"
            x-data="{ removeLogo: false, removeBusinessLicense: false }"
            class="admin-card space-y-5 p-5">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="admin-first-name" class="mb-2 block text-sm font-bold text-slate-700">نام</label><input
                        id="admin-first-name" name="first_name" value="{{ old('first_name', $store?->first_name) }}"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div><label for="admin-last-name" class="mb-2 block text-sm font-bold text-slate-700">نام
                        خانوادگی</label><input id="admin-last-name" name="last_name"
                        value="{{ old('last_name', $store?->last_name) }}"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div><label for="admin-national-id" class="mb-2 block text-sm font-bold text-slate-700">کد
                        ملی</label><input id="admin-national-id" name="national_id"
                        value="{{ old('national_id', $store?->national_id ?? $user->national_id) }}" inputmode="numeric"
                        maxlength="10" dir="ltr"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div><label for="admin-store-phone" class="mb-2 block text-sm font-bold text-slate-700">تلفن
                        تماس</label><input id="admin-store-phone" name="contact_phone"
                        value="{{ old('contact_phone', $store?->contact_phone ?? $user->mobile) }}" inputmode="numeric"
                        dir="ltr"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"
                        placeholder="09120000000"></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="admin-license-number" class="mb-2 block text-sm font-bold text-slate-700">شماره
                        صنفی</label><input id="admin-license-number" name="business_license_number"
                        value="{{ old('business_license_number', $store?->business_license_number) }}"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <p class="mb-2 block text-sm font-bold text-slate-700">تاریخ اعتبار جواز کسب</p>
                    <x-persian-date-select name="business_license_expires_at" :value="old('business_license_expires_at', $storeExpiry)"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary" />
                    <p class="mt-1 text-[11px] text-slate-400">روز، ماه و سال شمسی را انتخاب کنید.</p>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="admin-store-name" class="mb-2 block text-sm font-bold text-slate-700">نام
                        فروشگاه</label><input id="admin-store-name" name="name"
                        value="{{ old('name', $store?->name ?? $user->name) }}" required
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div><label for="admin-store-slug" class="mb-2 block text-sm font-bold text-slate-700">شناسه اینترنتی انگلیسی
                        فروشگاه</label>
                    <div class="flex items-center gap-2" dir="ltr"><span
                            class="rounded-xl bg-slate-100 px-3 py-3 text-xs text-slate-500">/store/</span><input
                            id="admin-store-slug" name="slug" value="{{ old('slug', $store?->slug) }}"
                            dir="ltr" required
                            class="min-w-0 flex-1 rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"
                            placeholder="mobile-shop"></div>
                    <p class="mt-1 text-[11px] text-slate-400">این بخش فقط برای URL است و مدیریت می‌تواند آن را اصلاح کند؛ کاربر پس از ثبت امکان تغییر ندارد.</p>
                    @error('slug')
                        <p class="mt-1 text-xs font-bold text-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div x-data="storefrontLocation($el.dataset.citiesEndpoint, $el.dataset.provinceId, $el.dataset.cityId)" data-cities-endpoint="{{ url('/locations/provinces') }}"
                data-province-id="{{ old('province_id', $store?->province_id) }}"
                data-city-id="{{ old('city_id', $store?->city_id) }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div><label for="admin-store-province"
                        class="mb-2 block text-sm font-bold text-slate-700">استان</label><select
                        id="admin-store-province" name="province_id" x-model="provinceId" @change="loadCities()"
                        class="w-full rounded-xl border-slate-200 text-sm">
                        <option value="">انتخاب استان</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select></div>
                <div><label for="admin-store-city"
                        class="mb-2 block text-sm font-bold text-slate-700">شهر</label><select id="admin-store-city"
                        name="city_id" x-model="cityId" :disabled="!provinceId || citiesLoading"
                        class="w-full rounded-xl border-slate-200 text-sm disabled:bg-slate-100">
                        <option value="">انتخاب شهر</option><template x-for="city in cities"
                            :key="city.id">
                            <option :value="city.id" x-text="city.name"></option>
                        </template>
                    </select>
                    <p x-show="citiesLoading" class="mt-1 text-[11px] text-slate-400">در حال دریافت شهرها...</p>
                </div>
            </div>
            <div><label for="admin-store-address" class="mb-2 block text-sm font-bold text-slate-700">آدرس دقیق فروشگاه (فارسی یا انگلیسی)</label>
                <textarea id="admin-store-address" name="address" rows="3"
                    dir="rtl" class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">{{ old('address', $store?->address) }}</textarea>
                <p class="mt-1 text-[11px] text-slate-400">آدرس دقیق می‌تواند فارسی یا انگلیسی باشد.</p>
                @error('address')
                    <p class="mt-1 text-xs font-bold text-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="admin-store-logo" class="mb-2 block text-sm font-bold text-slate-700">لوگوی
                        فروشگاه</label><input id="admin-store-logo" name="logo" type="file"
                        accept=".jpg,.jpeg,.png,.webp" @change="removeLogo = false"
                        class="w-full rounded-xl border border-slate-200 p-2 text-sm">
                    @if ($store?->logo_path)
                        <div x-show="!removeLogo" class="relative mt-3 w-fit">
                            <img src="{{ asset('storage/'.$store->logo_path) }}" alt="لوگوی فعلی فروشگاه"
                                class="h-28 w-28 rounded-xl border border-slate-200 object-cover">
                            <button type="button" @click="removeLogo = true"
                                class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-error text-white shadow"
                                aria-label="حذف لوگوی فعلی فروشگاه"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                        </div>
                    @endif
                    <input type="hidden" name="remove_logo" value="1" x-bind:disabled="!removeLogo">
                </div>
                <div><label for="admin-business-license" class="mb-2 block text-sm font-bold text-slate-700">تصویر
                        جواز کسب</label><input id="admin-business-license" name="business_license" type="file"
                        accept=".jpg,.jpeg,.png,.webp" @change="removeBusinessLicense = false"
                        class="w-full rounded-xl border border-slate-200 p-2 text-sm">
                    @if ($store?->business_license_path)
                        <div x-show="!removeBusinessLicense" class="relative mt-3 w-fit">
                            <img src="{{ asset('storage/'.$store->business_license_path) }}" alt="تصویر فعلی جواز کسب"
                                class="h-28 w-28 rounded-xl border border-slate-200 object-cover">
                            <button type="button" @click="removeBusinessLicense = true"
                                class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-error text-white shadow"
                                aria-label="حذف تصویر فعلی جواز کسب"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                        </div>
                    @endif
                    <input type="hidden" name="remove_business_license" value="1" x-bind:disabled="!removeBusinessLicense">
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label
                    class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4"><span><strong
                            class="block text-sm font-black text-slate-800">نمایش عمومی فروشگاه</strong><small
                            class="mt-1 block text-xs text-slate-400">فروشگاه تأییدشده برای بازدیدکنندگان قابل مشاهده
                            باشد.</small></span><input type="checkbox" name="store_enabled" value="1"
                        @checked(old('store_enabled', $store?->is_enabled ?? false))
                        class="h-5 w-5 rounded border-slate-300 text-primary focus:ring-primary"></label>
                <label
                    class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-error/15 bg-error/5 p-4"><span><strong
                            class="block text-sm font-black text-error">غیرفعال‌سازی مدیریتی</strong><small
                            class="mt-1 block text-xs text-slate-500">فروشگاه حتی با فعال‌بودن گزینه بالا نمایش داده
                            نشود.</small></span><input type="checkbox" name="admin_disabled" value="1"
                        @checked(old('admin_disabled', $store?->is_admin_disabled ?? false))
                        class="h-5 w-5 rounded border-slate-300 text-error focus:ring-error"></label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="admin-approval" class="mb-2 block text-sm font-bold text-slate-700">وضعیت
                        درخواست</label><select id="admin-approval" name="approval_status"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                        <option value="pending" @selected(old('approval_status', $store?->approval_status ?? 'pending') === 'pending')>در انتظار بررسی</option>
                        <option value="approved" @selected(old('approval_status', $store?->approval_status) === 'approved')>تأییدشده</option>
                        <option value="rejected" @selected(old('approval_status', $store?->approval_status) === 'rejected')>ردشده</option>
                    </select></div>
                <div>
                    <label for="admin-listing-approval-mode" class="mb-2 block text-sm font-bold text-slate-700">مجوز ثبت آگهی</label>
                    <select id="admin-listing-approval-mode" name="listing_approval_mode"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                        <option value="manual" @selected(old('listing_approval_mode', $store?->listing_approval_mode ?? \App\Models\SellerStore::LISTING_APPROVAL_MANUAL) === \App\Models\SellerStore::LISTING_APPROVAL_MANUAL)>با تأیید مدیریت</option>
                        <option value="free" @selected(old('listing_approval_mode', $store?->listing_approval_mode ?? \App\Models\SellerStore::LISTING_APPROVAL_MANUAL) === \App\Models\SellerStore::LISTING_APPROVAL_FREE)>ثبت آزاد (بدون تأیید مدیریت)</option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">در حالت ثبت آزاد، آگهی‌های جدید این فروشگاه مستقیم منتشر می‌شوند.</p>
                </div>
                <div><label for="admin-sort-order" class="mb-2 block text-sm font-bold text-slate-700">اولویت نمایش
                        (Sort)</label><input id="admin-sort-order" name="sort_order" type="number" min="0"
                        value="{{ old('sort_order', $store?->sort_order ?? 0) }}"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary">
                    <p class="mt-1 text-[11px] text-slate-400">عدد کمتر، جایگاه بالاتر؛ صفر یعنی نمایش تصادفی.</p>
                </div>
                <div class="sm:col-span-2"><label for="admin-rejection-reason"
                        class="mb-2 block text-sm font-bold text-slate-700">علت رد درخواست <span
                            class="text-xs font-normal text-slate-400">(در صورت انتخاب وضعیت ردشده)</span></label>
                    <textarea id="admin-rejection-reason" name="rejection_reason" rows="3" maxlength="1000"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-primary focus:ring-primary"
                        placeholder="مثلاً تصویر جواز کسب خوانا نیست.">{{ old('rejection_reason', $store?->rejection_reason) }}</textarea>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-5">
                <h3 class="text-sm font-black text-slate-800">کانال‌های ارتباطی</h3>
                <p class="mt-1 text-xs text-slate-400">نام کاربری کافی است و نیازی به واردکردن آدرس کامل سایت نیست.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label for="admin-instagram" class="mb-2 block text-xs font-bold text-slate-600">نام کاربری
                            اینستاگرام</label><input id="admin-instagram" name="instagram_url"
                            value="{{ old('instagram_url', $store?->instagram_url) }}" dir="ltr"
                            placeholder="مثلاً digistocki" class="w-full rounded-xl border-slate-200 text-sm"></div>
                    <div><label for="admin-telegram" class="mb-2 block text-xs font-bold text-slate-600">نام کاربری
                            تلگرام</label><input id="admin-telegram" name="telegram_url"
                            value="{{ old('telegram_url', $store?->telegram_url) }}" dir="ltr"
                            placeholder="مثلاً digistocki" class="w-full rounded-xl border-slate-200 text-sm"></div>
                    <div><label for="admin-whatsapp" class="mb-2 block text-xs font-bold text-slate-600">نام کاربری
                            واتساپ</label><input id="admin-whatsapp" name="whatsapp_url"
                            value="{{ old('whatsapp_url', $defaultWhatsapp) }}" dir="ltr"
                            placeholder="مثلاً 989120000000" class="w-full rounded-xl border-slate-200 text-sm"></div>
                    <div><label for="admin-bale" class="mb-2 block text-xs font-bold text-slate-600">نام کاربری
                            بله</label><input id="admin-bale" name="bale_url"
                            value="{{ old('bale_url', $store?->bale_url) }}" dir="ltr"
                            placeholder="مثلاً digistocki" class="w-full rounded-xl border-slate-200 text-sm"></div>
                    <div><label for="admin-rubika" class="mb-2 block text-xs font-bold text-slate-600">نام کاربری
                            روبیکا</label><input id="admin-rubika" name="rubika_url"
                            value="{{ old('rubika_url', $store?->rubika_url) }}" dir="ltr"
                            placeholder="مثلاً digistocki" class="w-full rounded-xl border-slate-200 text-sm"></div>
                </div>
            </div>
            <div
                class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('admin.users.edit', $user) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-200"><x-heroicon-o-user
                        class="h-5 w-5" />ویرایش اطلاعات کاربر</a><button
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-check
                        class="h-5 w-5" />ذخیره اطلاعات فروشگاه</button></div>
        </form>
    </section>
</x-admin-layout>
