@php
    $storeExpiry = $store?->business_license_expires_at
        ? \App\Support\PersianDate::format($store->business_license_expires_at)
        : '';
    $defaultWhatsapp = $store?->whatsapp_url
        ?: \App\Support\SocialHandle::fromMobile($store?->contact_phone ?? auth()->user()->mobile);
    $nameParts = preg_split('/\s+/', trim((string) auth()->user()->name), 2);
@endphp
<x-app-layout title="ثبت فروشگاه">
    <x-slot name="header">
        <div class="flex items-center gap-3"><span
                class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary"><x-heroicon-o-building-storefront
                    class="h-6 w-6" /></span>
            <div>
                <p class="text-xs font-bold text-primary">فروشنده دیجی استوکی</p>
                <h1 class="mt-1 text-xl font-black text-neutral">{{ $store ? 'ویرایش اطلاعات فروشگاه' : 'ثبت فروشگاه' }}
                </h1>
            </div>
        </div>
    </x-slot>
    <section class="space-y-5 px-4 py-6">
        @if (session('status'))
            <x-flowbite-alert type="success">{{ session('status') }}</x-flowbite-alert>
        @endif
        @if ($errors->any())<x-flowbite-alert type="danger">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-flowbite-alert>@endif
        @if ($store?->isPending())
            <div
                class="flex items-start gap-3 rounded-lg border border-warning/20 bg-warning/10 p-4 text-sm font-bold text-amber-900">
                <x-heroicon-o-clock class="mt-0.5 h-5 w-5 shrink-0" /><span>اطلاعات فروشگاه شما ثبت شده و پس از بررسی
                    مدیریت نتیجه اعلام می‌شود. تا آن زمان می‌توانید آگهی ثبت کنید.</span></div>
        @endif
        @if ($store?->isRejected())
            <div class="rounded-lg border border-error/20 bg-error/10 p-4 text-sm text-error">
                <p class="font-black">درخواست فروشگاه نیاز به اصلاح دارد.</p>
                <p class="mt-1 leading-6">
                    {{ $store->rejection_reason ?: 'لطفاً اطلاعات را بررسی و دوباره ارسال کنید.' }}</p>
            </div>
        @endif
        @if ($store?->isApproved())
            <div class="rounded-lg border border-success/20 bg-success/10 p-4 text-sm font-bold text-success">فروشگاه
                شما تأیید شده است. اطلاعات قابل ویرایش هستند، اما آدرس فروشگاه قابل تغییر نیست.</div>
        @endif

        @if (! $store?->isPending())
            <div class="rounded-lg border border-primary/20 bg-primary-50 p-4 text-sm text-neutral" role="note">
                <div class="flex items-center gap-2 font-black text-primary">
                    <x-heroicon-o-information-circle class="h-5 w-5" />
                    <span>تطابق اطلاعات درخواست‌کننده</span>
                </div>
                <p class="mt-2 text-xs leading-6 text-slate-600">نام و نام خانوادگی، کد ملی و شماره تماس درخواست‌کننده باید با مدارک جواز کسب مطابقت داشته باشند.</p>
            </div>
            <form method="POST" action="{{ route('storefront.update') }}" enctype="multipart/form-data"
                x-data="{ removeLogo: false, removeBusinessLicense: false, removeExteriorImage: false }"
                class="space-y-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="store-first-name" class="mb-2 block text-sm font-bold text-neutral">نام <span
                            class="text-error">*</span></label><input id="store-first-name" name="first_name"
                        value="{{ old('first_name', $store?->first_name ?? ($nameParts[0] ?? '')) }}" required
                        class="public-input focus:ring-2 focus:ring-primary/30"></div>
                <div><label for="store-last-name" class="mb-2 block text-sm font-bold text-neutral">نام خانوادگی <span
                            class="text-error">*</span></label><input id="store-last-name" name="last_name"
                        value="{{ old('last_name', $store?->last_name ?? ($nameParts[1] ?? '')) }}" required
                        class="public-input"></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="store-national-id" class="mb-2 block text-sm font-bold text-neutral">کد ملی <span
                            class="text-error">*</span></label><input id="store-national-id" name="national_id"
                        value="{{ old('national_id', $store?->national_id ?? auth()->user()->national_id) }}"
                        inputmode="numeric" maxlength="10" dir="ltr" required class="public-input"
                        placeholder="۰۰۱۲۳۴۵۶۷۸">
                    <p class="mt-1 text-[11px] text-slate-400">کد ملی ۱۰ رقمی ایرانی وارد کنید.</p>
                </div>
                <div><label for="store-phone" class="mb-2 block text-sm font-bold text-neutral">شماره تماس <span
                            class="text-error">*</span></label><input id="store-phone" name="contact_phone"
                        value="{{ old('contact_phone', $store?->contact_phone ?? auth()->user()->mobile) }}"
                        inputmode="numeric" dir="ltr" required class="public-input" placeholder="09120000000">
                    <p class="mt-1 text-[11px] text-slate-400">شماره موبایل باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.</p>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="store-license-number" class="mb-2 block text-sm font-bold text-neutral">شماره صنفی
                        <span class="text-error">*</span></label><input id="store-license-number"
                        name="business_license_number"
                        value="{{ old('business_license_number', $store?->business_license_number) }}" required
                        class="public-input">
                    <p class="mt-1 text-[11px] text-slate-400">شماره صنفی باید مطابق جواز کسب درخواست‌کننده باشد.</p>
                </div>
                <div>
                    <p class="mb-2 block text-sm font-bold text-neutral">تاریخ اتمام اعتبار جواز کسب <span
                            class="text-error">*</span></p><x-persian-date-select name="business_license_expires_at"
                        :value="old('business_license_expires_at', $storeExpiry)" :required="true" class="public-select" />
                    <p class="mt-1 text-[11px] text-slate-400">تاریخ اتمام جواز باید از امروز به بعد و مطابق مدرک جواز کسب باشد.</p>
                </div>
            </div>
            <div><label for="store-name" class="mb-2 block text-sm font-bold text-neutral">نام فروشگاه <span
                        class="text-error">*</span></label><input id="store-name" name="name"
                    value="{{ old('name', $store?->name ?? auth()->user()->name) }}" required class="public-input"
                    placeholder="مثلاً فروشگاه موبایل رضایی"></div>
            <div><label for="store-slug" class="mb-2 block text-sm font-bold text-neutral">شناسه اینترنتی انگلیسی فروشگاه <span
                        class="text-error">*</span></label>
                <div class="flex items-center gap-2" dir="ltr"><span
                        class="shrink-0 rounded-lg bg-slate-100 px-3 py-3 text-xs text-slate-500">/store/</span><input
                        id="store-slug" name="slug" value="{{ old('slug', $store?->slug) }}"
                        @disabled($store) dir="ltr" required
                        class="public-input min-w-0 flex-1 disabled:bg-slate-100 disabled:text-slate-500"
                        placeholder="mobile-rezaei"></div>
                <p class="mt-1 text-[11px] text-slate-400">این بخش فقط برای URL است و باید با حروف انگلیسی کوچک، عدد و خط تیره وارد شود؛ پس از ایجاد قابل تغییر نیست.</p>
                @error('slug')
                    <p class="mt-1 text-xs font-bold text-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div x-data="storefrontLocation($el.dataset.citiesEndpoint, $el.dataset.provinceId, $el.dataset.cityId)" data-cities-endpoint="{{ url('/locations/provinces') }}"
                data-province-id="{{ old('province_id', $store?->province_id) }}"
                data-city-id="{{ old('city_id', $store?->city_id) }}" class="grid grid-cols-2 gap-2">
                <div><label for="store-province" class="mb-2 block text-sm font-bold text-neutral">استان <span
                            class="text-error">*</span></label><select id="store-province" name="province_id"
                        x-model="provinceId" @change="loadCities()" class="public-select" required>
                        <option value="">انتخاب استان</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                        @endforeach
                    </select></div>
                <div><label for="store-city" class="mb-2 block text-sm font-bold text-neutral">شهر <span
                            class="text-error">*</span></label><select id="store-city" name="city_id"
                        x-model="cityId" :disabled="!provinceId || citiesLoading" class="public-select" required>
                        <option value="">انتخاب شهر</option><template x-for="city in cities"
                            :key="city.id">
                            <option :value="city.id" x-text="city.name"></option>
                        </template>
                    </select>
                    <p x-show="citiesLoading" class="mt-1 text-[11px] text-slate-400">در حال دریافت شهرها...</p>
                </div>
            </div>
            <div><label for="store-address" class="mb-2 block text-sm font-bold text-neutral">آدرس دقیق فروشگاه (فارسی یا انگلیسی) <span
                        class="text-error">*</span></label>
                <textarea id="store-address" name="address" rows="3" dir="rtl" required class="public-input">{{ old('address', $store?->address) }}</textarea>
                <p class="mt-1 text-[11px] text-slate-400">آدرس دقیق را می‌توانید به فارسی یا انگلیسی وارد کنید.</p>
                @error('address')
                    <p class="mt-1 text-xs font-bold text-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="store-license" class="mb-2 block text-sm font-bold text-neutral">تصویر جواز کسب <span
                            class="text-error">*</span></label><input id="store-license" name="business_license"
                        type="file" accept=".jpg,.jpeg,.png,.webp,.heic,.heif" @required(!$store?->business_license_path)
                        @change="removeBusinessLicense = false" class="public-file">
                    <label for="store-license-camera"
                        class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs font-bold text-primary hover:bg-primary-100">
                        <x-heroicon-o-camera class="h-4 w-4" /> انتخاب با دوربین
                    </label>
                    <input id="store-license-camera" name="business_license_camera" type="file" accept="image/*"
                        capture="environment" @change="removeBusinessLicense = false" class="sr-only">
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
                    <p class="mt-1 text-[11px] text-slate-400">حداکثر حجم تصویر {{ \App\Support\PersianNumber::digits($maxImageUploadMb) }} مگابایت.</p>
                </div>
                <div><label for="store-logo" class="mb-2 block text-sm font-bold text-neutral">لوگوی فروشگاه <span
                            class="text-error">*</span></label>
                    <input id="store-logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,.heic,.heif"
                        @required(!$store?->logo_path) @change="removeLogo = false" class="public-file">
                    <label for="store-logo-camera"
                        class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs font-bold text-primary hover:bg-primary-100">
                        <x-heroicon-o-camera class="h-4 w-4" /> انتخاب با دوربین
                    </label>
                    <input id="store-logo-camera" name="logo_camera" type="file" accept="image/*" capture="environment"
                        @change="removeLogo = false" class="sr-only">
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
                    <p class="mt-1 text-[11px] text-slate-400">حداکثر حجم تصویر {{ \App\Support\PersianNumber::digits($maxImageUploadMb) }} مگابایت.</p>
                </div>
            </div>
            <div>
                <label for="store-exterior-image" class="mb-2 block text-sm font-bold text-neutral">تصویر نمایش بیرون (سردر) فروشگاه
                    <span class="text-xs font-normal text-slate-400">(اختیاری)</span></label>
                <input id="store-exterior-image" name="exterior_image" type="file"
                    accept=".jpg,.jpeg,.png,.webp,.heic,.heif" @change="removeExteriorImage = false" class="public-file">
                <label for="store-exterior-image-camera"
                    class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-xs font-bold text-primary hover:bg-primary-100">
                    <x-heroicon-o-camera class="h-4 w-4" /> انتخاب با دوربین
                </label>
                <input id="store-exterior-image-camera" name="exterior_image_camera" type="file" accept="image/*"
                    capture="environment" @change="removeExteriorImage = false" class="sr-only">
                @if ($store?->exterior_image_path)
                    <div x-show="!removeExteriorImage" class="relative mt-3 w-fit">
                        <img src="{{ asset('storage/'.$store->exterior_image_path) }}" alt="تصویر فعلی نمای بیرونی فروشگاه"
                            class="h-28 w-48 rounded-xl border border-slate-200 object-cover">
                        <button type="button" @click="removeExteriorImage = true"
                            class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-error text-white shadow"
                            aria-label="حذف تصویر فعلی نمای بیرونی فروشگاه"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                    </div>
                @endif
                <input type="hidden" name="remove_exterior_image" value="1" x-bind:disabled="!removeExteriorImage">
                <p class="mt-1 text-[11px] text-slate-400">برای نمایش تصویر سردر در بالای صفحه فروشگاه؛ حداکثر حجم {{ \App\Support\PersianNumber::digits($maxImageUploadMb) }} مگابایت.</p>
            </div>
            @if ($store?->approval_status === 'approved')
                <label
                    class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border border-slate-100 bg-slate-50 p-4"><span><strong
                            class="block text-sm font-black text-neutral">نمایش عمومی فروشگاه</strong><small
                            class="mt-1 block text-xs text-slate-400">فروشگاه تأییدشده برای کاربران قابل مشاهده
                            باشد.</small></span><input type="checkbox" name="store_enabled" value="1"
                        @checked(old('store_enabled', $store->is_enabled)) class="public-check h-5 w-5"></label>
            @endif
            <div class="border-t border-slate-100 pt-5">
                <h2 class="text-base font-black text-neutral">کانال‌های ارتباطی (اختیاری)</h2>
                <p class="mt-1 text-xs text-slate-400">نام کاربری کافی است و نیازی به واردکردن آدرس کامل سایت نیست.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label for="store-instagram" class="mb-2 block text-sm font-bold text-neutral">نام کاربری
                            اینستاگرام</label><input id="store-instagram" name="instagram_url"
                            value="{{ old('instagram_url', $store?->instagram_url) }}" dir="ltr"
                            class="public-input" placeholder="مثلاً digistocki"></div>
                    <div><label for="store-telegram" class="mb-2 block text-sm font-bold text-neutral">نام کاربری
                            تلگرام</label><input id="store-telegram" name="telegram_url"
                            value="{{ old('telegram_url', $store?->telegram_url) }}" dir="ltr"
                            class="public-input" placeholder="مثلاً digistocki"></div>
                    <div><label for="store-whatsapp" class="mb-2 block text-sm font-bold text-neutral">نام کاربری
                            واتساپ</label><input id="store-whatsapp" name="whatsapp_url"
                            value="{{ old('whatsapp_url', $defaultWhatsapp) }}" dir="ltr"
                            class="public-input" placeholder="مثلاً 989120000000"></div>
                    <div><label for="store-bale" class="mb-2 block text-sm font-bold text-neutral">نام کاربری
                            بله</label><input id="store-bale" name="bale_url"
                            value="{{ old('bale_url', $store?->bale_url) }}" dir="ltr" class="public-input"
                            placeholder="مثلاً digistocki"></div>
                    <div><label for="store-rubika" class="mb-2 block text-sm font-bold text-neutral">نام کاربری
                            روبیکا</label><input id="store-rubika" name="rubika_url"
                            value="{{ old('rubika_url', $store?->rubika_url) }}" dir="ltr" class="public-input"
                            placeholder="مثلاً digistocki"></div>
                </div>
            </div>
            <button class="mobile-button w-full bg-primary text-white hover:bg-primary-600"><x-heroicon-o-check
                    class="h-5 w-5" />{{ $store ? 'ذخیره اطلاعات فروشگاه' : 'ثبت درخواست فروشگاه' }}</button>
            </form>
        @else
            <section class="space-y-4 rounded-lg border border-warning/20 bg-white p-5 shadow-sm"
                aria-label="اطلاعات درخواست فروشگاه">
                <div class="flex items-start gap-3 rounded-lg bg-warning/10 p-4 text-sm font-bold text-amber-900">
                    <x-heroicon-o-clock class="mt-0.5 h-5 w-5 shrink-0" />
                    <p>تا زمان اعلام نتیجه مدیریت، فرم ویرایش در دسترس نیست. پس از تأیید یا رد درخواست، امکان ادامه فرآیند برای شما فعال می‌شود.</p>
                </div>
                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-xs text-slate-400">نام مالک</span><strong class="mt-1 block text-neutral">{{ trim($store->first_name.' '.$store->last_name) ?: auth()->user()->name }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-xs text-slate-400">نام فروشگاه</span><strong class="mt-1 block text-neutral">{{ $store->name }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-xs text-slate-400">شناسه اینترنتی</span><strong class="mt-1 block text-neutral" dir="ltr">/store/{{ $store->slug }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-xs text-slate-400">شماره تماس</span><strong class="mt-1 block text-neutral" dir="ltr">{{ $store->contact_phone }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-xs text-slate-400">موقعیت</span><strong class="mt-1 block text-neutral">{{ collect([$store->province?->name, $store->city?->name])->filter()->implode('، ') ?: 'ثبت نشده' }}</strong></div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="text-xs text-slate-400">تاریخ ثبت درخواست</span><strong class="mt-1 block text-neutral">{{ $store->application_submitted_at ? \App\Support\PersianDate::format($store->application_submitted_at, 'Y/m/d') : 'ثبت نشده' }}</strong></div>
                </div>
                <div class="rounded-lg bg-slate-50 p-3 text-sm"><span class="text-xs text-slate-400">آدرس دقیق</span><p class="mt-1 leading-7 text-neutral">{{ $store->address ?: 'ثبت نشده' }}</p></div>
            </section>
        @endif
    </section>
</x-app-layout>
