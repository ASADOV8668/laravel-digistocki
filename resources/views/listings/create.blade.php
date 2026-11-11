@php
    $clone = $cloneListing ?? null;
    $freeListing = $store?->allowsFreeListing() ?? false;
@endphp
<x-app-layout title="ثبت آگهی">
    <x-slot name="header">
        <div class="flex items-center gap-3"><span
                class="flex h-11 w-11 items-center justify-center rounded-lg bg-primary-50 text-primary"><x-heroicon-o-plus
                    class="h-6 w-6" /></span>
            <div>
                <p class="text-xs font-bold text-primary">فروش سریع‌تر</p>
                <h1 class="mt-1 text-xl font-black text-neutral">ثبت آگهی</h1>
            </div>
        </div>
    </x-slot>
    <section x-data="listingWizardWithMedia(@js($maxImageUploadMb), @js($maxImageUploadCount), @js(''), @js(route('listings.media.index')) )" data-title-language="{{ app(\App\Services\SystemOptions::class)->listingTitleLanguage() }}" data-models-endpoint="{{ url('/listings/models') }}" data-wizard-endpoint="{{ url('/listings/wizard/brands') }}"
        data-cities-endpoint="{{ url('/locations/provinces') }}"
        data-initial-brand-id="{{ old('brand_id', $clone?->brand_id) }}"
        data-initial-series-id="{{ old('phone_series_id', $clone?->phoneModel?->phone_series_id) }}"
        data-initial-model-id="{{ old('phone_model_id', $clone?->phone_model_id) }}"
        data-initial-province-id="{{ old('province_id', $store?->province_id) }}"
        data-initial-city-id="{{ old('city_id', $store?->city_id) }}" class="space-y-6 px-4 py-6">
        <script type="application/json" data-listing-initial-attributes>@json($initialModelAttributes)</script>
        <script type="application/json" data-listing-initial-values>@json($initialValues ?? old('attributes', []))</script>
        @if ($errors->any())<x-flowbite-alert type="danger">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-flowbite-alert>@endif

        <div x-show="false" class="rounded-lg bg-white p-4 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between text-[11px] font-bold"><span class="flex items-center gap-1.5"
                    :class="step >= 1 ? 'text-primary' : 'text-slate-400'"><span class="sr-only">۱. مدل</span><span
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-current/10">۱</span>مدل</span><span
                    class="flex items-center gap-1.5" :class="step >= 2 ? 'text-primary' : 'text-slate-400'"><span
                        class="sr-only">۲. مشخصات</span><span
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-current/10">۲</span>مشخصات</span><span
                    class="flex items-center gap-1.5" :class="step >= 3 ? 'text-primary' : 'text-slate-400'"><span
                        class="sr-only">۳. اطلاعات</span><span
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-current/10">۳</span>اطلاعات</span><span
                    class="flex items-center gap-1.5" :class="step >= 4 ? 'text-primary' : 'text-slate-400'"><span
                        class="sr-only">۴. ارسال</span><span
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-current/10">۴</span>ارسال</span>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-primary transition-all duration-300" :style="`width: ${step * 25}%`">
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('listings.store') }}" enctype="multipart/form-data"
            class="rounded-lg bg-white p-5 shadow-sm border border-slate-200">
            @csrf
            @include('listings.partials.fast-wizard')
            <div x-show="false" x-cloak class="space-y-4">
                <div>
                    <h2 class="text-lg font-black text-neutral">مدل گوشی را انتخاب کنید</h2>
                    <p class="mt-1 text-xs leading-6 text-slate-500">با انتخاب مدل، ویژگی‌های مخصوص همان دستگاه در مرحله
                        بعد بارگذاری می‌شود.</p>
                </div>
                <div><label class="mb-2 block text-sm font-bold text-neutral">برند</label><select name="legacy_brand_id"
                        data-listing-brand-picker data-model-target="#public-listing-model"
                        data-models-endpoint="{{ url('/listings/models') }}" x-model="brandId" @change="loadModels()"
                        required class="public-select">
                        <option value="">انتخاب برند</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" data-name-en="{{ $brand->name_en }}" @selected((string) old('brand_id') === (string) $brand->id)>{{ $brand->name }}
                                ({{ $brand->name_en }})</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="mb-2 block text-sm font-bold text-neutral">مدل گوشی</label><select
                        id="public-listing-model" name="legacy_phone_model_id" x-model="modelId" @change="loadAttributes"
                        required :disabled="!brandId || modelsLoading" class="public-select">
                        <option value="" x-text="modelsLoading ? 'در حال بارگذاری مدل‌ها...' : 'انتخاب مدل'">
                        </option><template x-for="model in models" :key="model.id">
                            <option :value="model.id"
                                x-text="`${model.label || model.name_fa || model.name} — ${model.secondary || model.name_en || model.name}`">
                            </option>
                        </template>
                    </select>
                    <p class="mt-1 text-xs text-slate-400">فقط مدل‌های برند انتخاب‌شده نمایش داده می‌شوند.</p>
                </div>
                <div class="rounded-lg bg-primary-50 p-3 text-xs leading-6 text-primary">انتخاب مدل، فرم مشخصات را
                    دقیقاً مطابق کاتالوگ همان گوشی تنظیم می‌کند.</div>
            </div>

            <div x-show="false" x-cloak class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-black text-neutral">مشخصات گوشی</h2>
                        <p class="mt-1 text-xs text-slate-500">مقادیر مربوط به مدل انتخاب‌شده را وارد کنید.</p>
                    </div><span x-show="attributesLoading" class="text-xs text-slate-400">در حال بارگذاری...</span>
                </div>
                <div x-show="!attributesLoading && !attributes.length"
                    class="rounded-lg bg-warning/10 p-3 text-xs leading-6 text-neutral">برای این مدل هنوز ویژگی‌ای تعریف
                    نشده است.</div>
                <div x-show="!attributesLoading"><x-listing-attribute-repeater :attribute-definitions="$initialModelAttributes" :values="$initialValues ?? old('attributes', [])" />
                </div>
            </div>

            <div x-show="fastComplete()" x-cloak class="mt-6 space-y-4">
                <div>
                    <h2 class="text-lg font-black text-neutral">اطلاعات آگهی</h2>
                    <p class="mt-1 text-xs text-slate-500">عنوان روشن، قیمت دقیق و موقعیت آگهی باعث اعتماد بیشتر خریدار
                        می‌شود.</p>
                </div>
                <div><label class="mb-2 block text-sm font-bold text-neutral">عنوان آگهی</label><input id="public-listing-title" data-auto-listing-title="{{ old('title', $clone?->title ?? '') === '' ? 'true' : 'false' }}" name="title"
                        value="{{ old('title', $clone?->title ?? '') }}" required
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-neutral shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/30"
                        placeholder="پس از انتخاب مدل و ویژگی‌ها خودکار تکمیل می‌شود">
                    @if ($clone)
                        <input type="hidden" name="clone" value="{{ $clone->id }}">
                        <p class="mt-1 text-[11px] text-primary">ویژگی‌ها و تصاویر آگهی اصلی در صورت تغییر نکردن تصاویر،
                            کپی می‌شوند.</p>
                    @endif
                </div>
                <div x-data="{ priceOnRequest: @js((bool) old('price_on_request', $clone?->price_on_request ?? false)) }"><label class="mb-2 block text-sm font-bold text-neutral">قیمت
                        (تومان)</label><div x-data="moneyInput(@js(old('price', $clone?->price)))" x-show="!priceOnRequest"><input
                            x-model="display" @input="formatInput($event)" :required="!priceOnRequest" type="text"
                            inputmode="numeric" autocomplete="off" dir="ltr"
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 text-left text-sm text-neutral shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/30"
                            placeholder="مثلاً ۳۲٬۰۰۰٬۰۰۰"><input type="hidden" name="price" :value="raw"></div>
                    @if ($allowContactPrice)
                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm font-bold text-neutral"><input
                                type="checkbox" name="price_on_request" value="1" x-model="priceOnRequest"
                                @checked(old('price_on_request', $clone?->price_on_request)) class="public-check"> قیمت را اعلام نمی‌کنم؛ تماس
                            بگیرید</label>
                    @endif
                </div>
                <label class="flex items-center gap-2 text-sm font-bold text-neutral"><input name="is_negotiable"
                        value="1" type="checkbox" @checked(old('is_negotiable', $clone?->is_negotiable)) class="public-check"> قیمت قابل
                    مذاکره است</label>
                <div class="grid grid-cols-2 gap-2">
                        <div><label class="mb-2 block text-sm font-bold text-neutral">استان</label><select
                            name="province_id" x-model="provinceId" @change="loadCities" required class="public-select">
                            <option value="">انتخاب استان</option>
                            @foreach ($provinces as $province)
                                <option value="{{ $province->id }}" @selected((string) old('province_id', $store?->province_id) === (string) $province->id)>{{ $province->name }}</option>
                            @endforeach
                        </select></div>
                    <div><label class="mb-2 block text-sm font-bold text-neutral">شهر</label><select name="city_id"
                            x-model="cityId" :disabled="!provinceId || citiesLoading" required class="public-select">
                            <option value="">انتخاب شهر</option><template x-for="city in cities"
                                :key="city.id">
                                <option :value="city.id" x-text="city.name"></option>
                            </template>
                        </select></div>
                </div>
                <div><label class="mb-2 block text-sm font-bold text-neutral">توضیحات</label>
                    <textarea name="description" rows="5"
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-neutral shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/30"
                        placeholder="وضعیت گوشی، لوازم همراه و شرایط فروش را بنویسید">{{ old('description', $clone?->description) }}</textarea>
                </div>
            </div>

            <div x-show="fastComplete()" x-cloak class="mt-6 space-y-4">
                <div>
                    <h2 class="text-lg font-black text-neutral">تصاویر و ارسال</h2>
                    <p class="mt-1 text-xs leading-6 text-slate-500">حداقل یک تصویر و حداکثر {{ \App\Support\PersianNumber::digits($maxImageUploadCount) }} تصویر، هر تصویر حداکثر {{ $maxImageUploadMb }} مگابایت. تصویر اول به‌عنوان تصویر اصلی نمایش داده می‌شود.</p>
                </div>
                <div class="space-y-3"><label
                        class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-primary/20 bg-primary-50/40 p-8 text-center transition hover:border-primary/50"><span
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-white text-primary shadow-sm"><x-heroicon-o-photo
                                class="h-6 w-6" /></span><span class="mt-3 text-sm font-black text-neutral">تصاویر
                            گوشی را انتخاب کنید</span><span class="mt-1 text-xs text-slate-400">JPG، PNG یا WEBP ·
                            حداکثر {{ $maxImageUploadMb }}MB</span><input @change="select($event)" type="file"
                            name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,.heic,.heif" class="sr-only"></label>
                    <div class="flex items-center justify-between gap-2">
                        <button type="button" @click.prevent.stop="openMedia()"
                            class="inline-flex items-center gap-2 rounded-lg border border-primary/20 bg-white px-3 py-2 text-xs font-bold text-primary shadow-sm hover:bg-primary-50">
                            <x-heroicon-o-photo class="h-4 w-4" /> انتخاب از تصاویر قبلی
                        </button>
                        <span class="text-[10px] text-slate-400" x-text="mediaCount() + ' تصویر انتخاب شده'"></span>
                    </div>
                    <div x-show="mediaOpen" x-cloak class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-black text-neutral">رسانه‌های قبلی شما</p>
                            <button type="button" @click="mediaOpen = false" class="text-xs font-bold text-slate-400">بستن</button>
                        </div>
                        <p x-show="mediaLoading" class="mt-3 text-xs text-slate-400">در حال بارگذاری تصاویر...</p>
                        <p x-show="!mediaLoading && !mediaItems.length" class="mt-3 text-xs text-slate-400">هنوز تصویر قبلی‌ای برای انتخاب وجود ندارد.</p>
                        <p x-show="mediaMessage" x-text="mediaMessage" class="mt-2 rounded-lg bg-error/10 px-3 py-2 text-xs font-bold text-error"></p>
                        <div x-show="!mediaLoading && mediaItems.length" class="mt-3 grid grid-cols-4 gap-2">
                            <template x-for="media in mediaItems" :key="media.id">
                                <button type="button" @click="toggleMedia(media)"
                                    class="relative overflow-hidden rounded-lg border-2 bg-slate-50 text-right"
                                    :class="selectedMedia.some(item => String(item.id) === String(media.id)) ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200'">
                                    <img :src="media.thumbnail_url || media.url" :alt="media.name" class="h-20 w-full object-cover">
                                    <span class="block truncate px-1 py-1 text-[9px] text-slate-500" x-text="media.name"></span>
                                    <span x-show="selectedMedia.some(item => String(item.id) === String(media.id))"
                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-white">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-show="selectedMedia.length" x-cloak class="grid grid-cols-4 gap-2">
                        <template x-for="media in selectedMedia" :key="'selected-media-' + media.id">
                            <div class="relative overflow-hidden rounded-lg border-2 bg-slate-50"
                                :class="String(mediaPrimaryId) === String(media.id) ? 'border-success ring-2 ring-success/20' : 'border-slate-200'">
                                <button type="button" @click="setMediaPrimary(media.id)" class="block w-full">
                                    <img :src="media.thumbnail_url || media.url" :alt="media.name" class="h-20 w-full object-cover">
                                </button>
                                <button type="button" @click.stop="toggleMedia(media)"
                                    class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-error text-white shadow-lg"
                                    aria-label="حذف تصویر انتخاب‌شده"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                                <span x-show="String(mediaPrimaryId) === String(media.id)"
                                    class="absolute bottom-1 right-1 flex h-6 w-6 items-center justify-center rounded-full bg-success text-white shadow-lg">✓</span>
                            </div>
                        </template>
                    </div>
                    <template x-for="media in selectedMedia" :key="'media-input-' + media.id">
                        <input type="hidden" name="media_ids[]" :value="media.id">
                    </template>
                    <input type="hidden" name="media_primary_id" x-model="mediaPrimaryId">
                    <div x-show="previews.length" x-cloak class="grid grid-cols-4 gap-2"><template
                            x-for="(preview, index) in previews" :key="preview.url">
                            <div role="button" tabindex="0" @click="setPrimary(index)"
                                @keydown.enter.prevent="setPrimary(index)" @keydown.space.prevent="setPrimary(index)"
                                class="relative overflow-hidden rounded-lg border-2 bg-slate-50 text-right transition"
                                :class="primaryImageId === '' && String(primaryIndex) === String(index) ? 'border-success ring-2 ring-success/20' : 'border-slate-200 hover:border-primary/50'"
                                :aria-label="`انتخاب ${preview.name} به‌عنوان تصویر اصلی`">
                                <button type="button" @click.stop="removePreview(index)"
                                    class="absolute right-1 top-1 z-10 flex h-6 w-6 items-center justify-center rounded-full bg-error text-white shadow-lg transition hover:bg-error/80"
                                    :aria-label="`حذف ${preview.name}`">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                </button>
                                <img :src="preview.url" :alt="preview.name" class="h-20 w-full object-cover">
                                <span x-show="primaryImageId === '' && String(primaryIndex) === String(index)"
                                    class="absolute bottom-1 right-1 flex h-6 w-6 items-center justify-center rounded-full bg-success text-white shadow-lg"
                                    aria-label="تصویر اصلی">✓</span>
                                <p x-text="preview.name" class="truncate px-1.5 py-1 text-[9px] text-slate-500"></p>
                            </div>
                        </template></div>
                    <input type="hidden" name="primary_image_index" x-model="primaryIndex">
                    <input type="hidden" name="primary_image_id" x-model="primaryImageId">
                    @if ($clone?->images->isNotEmpty())
                        <div class="rounded-lg border border-primary/10 bg-primary-50/40 p-3">
                            <div class="flex items-center gap-2 text-xs font-black text-primary">
                                <x-heroicon-o-document-duplicate class="h-4 w-4" />
                                تصاویر آگهی اصلی برای کپی
                            </div>
                            <p class="mt-1 text-[11px] leading-5 text-slate-500">اگر تصویر جدیدی انتخاب نکنید، این تصاویر
                                همراه آگهی کپی می‌شوند.</p>
                            <div class="mt-3 grid grid-cols-4 gap-2">
                                @foreach ($clone->images as $image)
                                    <div class="relative overflow-hidden rounded-lg border-2 {{ $image->is_primary ? 'border-success ring-2 ring-success/20' : 'border-slate-200' }} bg-white">
                                        <img src="{{ $image->displayUrl(true) }}"
                                            alt="تصویر آگهی اصلی" class="h-20 w-full object-cover" loading="lazy">
                                        @if ($image->is_primary)
                                            <span
                                                class="absolute bottom-1 right-1 flex h-6 w-6 items-center justify-center rounded-full bg-success text-white shadow-lg"
                                                aria-label="تصویر اصلی">✓</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <p x-show="previews.length" class="text-[10px] text-slate-400">برای تعیین تصویر اصلی روی همان تصویر کلیک کنید.</p>
                    <div class="flex items-center justify-between text-[10px] text-slate-400"><span
                            x-show="previews.length" x-text="`${previews.length} تصویر انتخاب شده`"></span><span
                            x-show="!previews.length">هنوز تصویری انتخاب نشده است</span><span>حداکثر {{ \App\Support\PersianNumber::digits($maxImageUploadCount) }} تصویر</span></div>
                    <p x-show="invalid" x-text="message"
                        class="rounded-lg bg-error/10 px-3 py-2 text-xs font-bold text-error"></p>
                </div>
                <div class="rounded-lg bg-warning/10 p-4 text-xs leading-7 text-neutral">
                    {{ $freeListing ? 'آگهی پس از ثبت بلافاصله در سایت منتشر می‌شود.' : 'آگهی پس از ارسال در وضعیت «در انتظار بررسی» قرار می‌گیرد و پس از تأیید ادمین در سایت نمایش داده می‌شود.' }}
                </div>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-4"><button type="submit" x-show="fastComplete()"
                    class="mobile-button w-full bg-primary py-3.5 text-base font-black text-white shadow-lg shadow-primary/20 hover:bg-primary-600">{{ $freeListing ? 'ثبت آگهی' : 'ارسال برای بررسی' }}</button>
            </div>
        </form>
    </section>
</x-app-layout>
