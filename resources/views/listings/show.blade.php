@php
    $listingSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $listing->title,
        'description' => $listing->description ?: $listing->title,
        'image' => $listing->images->map(fn ($image) => asset('storage/'.$image->path))->values()->all(),
        'brand' => ['@type' => 'Brand', 'name' => $listing->brand->name],
        'model' => $listing->phoneModel->name_en ?: $listing->phoneModel->name,
        'offers' => [
            '@type' => 'Offer',
            'url' => url()->current(),
            'priceCurrency' => 'IRR',
            'availability' => 'https://schema.org/InStock',
            'itemCondition' => 'https://schema.org/UsedCondition',
        ],
    ];
    if (! $listing->price_on_request) {
        $listingSchema['offers']['price'] = (string) $listing->price;
    }
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'آگهی‌ها', 'item' => route('listings.index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $listing->title, 'item' => url()->current()],
        ],
    ];
@endphp
@push('head')
    <script type="application/ld+json">{!! json_encode($listingSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
<x-app-layout title="جزئیات آگهی">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('listings.index') }}" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" aria-label="بازگشت">
                    <x-heroicon-o-arrow-right class="h-5 w-5" />
                </a>
                <div>
                    <p class="text-[11px] font-bold text-primary">جزئیات محصول</p>
                    <h1 class="mt-0.5 text-xl font-black text-neutral">آگهی موبایل</h1>
                </div>
            </div>
            <span class="rounded-full bg-success/10 px-3 py-1.5 text-[10px] font-black text-success">تأییدشده</span>
        </div>
    </x-slot>

    <section class="space-y-5 px-4 py-6">
        @if (session('status'))
            <x-flowbite-alert type="success">{{ session('status') }}</x-flowbite-alert>
        @endif
        @if ($errors->any())
            <x-flowbite-alert type="danger">{{ $errors->first() }}</x-flowbite-alert>
        @endif

        <div id="listing-gallery" class="relative touch-pan-y overflow-hidden rounded-lg bg-slate-900 p-2 shadow-xl shadow-slate-900/10" data-carousel="static" data-carousel-interval="false" data-listing-carousel aria-roledescription="carousel" aria-label="تصاویر آگهی">
            @if ($listing->images->isNotEmpty())
                <div class="relative aspect-[4/3] overflow-hidden rounded-lg bg-slate-950">
                    @foreach ($listing->images as $image)
                        <div class="{{ $loop->first ? '' : 'hidden' }} duration-700 ease-in-out" data-carousel-item="{{ $loop->first ? 'active' : '' }}">
                            <button type="button" data-modal-target="listing-gallery-modal" data-modal-toggle="listing-gallery-modal" data-carousel-open-index="{{ $loop->index }}" class="block h-full w-full cursor-zoom-in focus:outline-none focus:ring-4 focus:ring-primary/40" aria-label="نمایش تصویر {{ $loop->iteration }} در حالت تمام‌صفحه"><img src="{{ asset('storage/'.$image->path) }}" alt="{{ $listing->title }}" class="absolute inset-0 block h-full w-full object-contain" decoding="async" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif></button>
                        </div>
                    @endforeach
                    <div class="pointer-events-none absolute inset-x-4 bottom-4 z-40 flex items-end justify-between gap-3 text-white">
                        <span class="rounded-full bg-slate-950/70 px-3 py-1.5 text-[10px] font-bold backdrop-blur">{{ \App\Support\PersianNumber::digits($listing->images->count()) }} تصویر</span>
                        <span class="rounded-full bg-slate-950/70 px-3 py-1.5 text-[10px] font-bold backdrop-blur">{{ \App\Support\PersianNumber::digits($listing->views_count) }} بازدید</span>
                    </div>
                </div>
                @if ($listing->images->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto px-1 pb-1" aria-label="انتخاب تصویر آگهی">
                        @foreach ($listing->images as $image)
                            <button type="button" data-carousel-slide-to="{{ $loop->index }}" data-gallery-thumbnail aria-label="نمایش تصویر {{ $loop->iteration }}" class="h-16 w-20 shrink-0 overflow-hidden rounded-lg border-2 border-transparent bg-slate-800 transition hover:border-primary focus:outline-none focus:ring-2 focus:ring-primary/70 {{ $loop->first ? 'border-primary' : '' }}">
                                <img src="{{ asset('storage/'.$image->path) }}" alt="تصویر کوچک {{ $loop->iteration }}" class="h-full w-full object-cover" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="flex h-64 items-center justify-center rounded-lg bg-gradient-to-br from-primary-50 via-white to-accent/10 text-primary">
                    <x-heroicon-o-device-phone-mobile class="h-24 w-24" />
                </div>
            @endif
        </div>

        @if ($listing->images->isNotEmpty())
            <div id="listing-gallery-modal" tabindex="-1" aria-hidden="true" data-modal-backdrop="dynamic" class="fixed inset-0 z-50 hidden h-full w-full overflow-y-auto">
                <div class="relative flex min-h-full w-full items-center justify-center p-2 sm:p-5">
                    <div class="relative h-[92vh] w-full max-w-6xl">
                        <button type="button" data-modal-hide="listing-gallery-modal" class="absolute right-3 top-3 z-40 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-lg backdrop-blur transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-white/50" aria-label="بستن نمایش تمام‌صفحه"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                        <div id="listing-gallery-fullscreen-carousel" class="relative h-full touch-pan-y overflow-hidden rounded-lg bg-slate-950" data-carousel="static" data-carousel-interval="false" data-listing-carousel aria-roledescription="carousel" aria-label="تصاویر آگهی در حالت تمام‌صفحه">
                            @foreach ($listing->images as $image)
                                <div class="{{ $loop->first ? '' : 'hidden' }} duration-700 ease-in-out" data-carousel-item="{{ $loop->first ? 'active' : '' }}">
                                    <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $listing->title }}" class="absolute block h-full w-full object-contain" decoding="async" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                                </div>
                            @endforeach
                            @if ($listing->images->count() > 1)
                                <button type="button" data-carousel-prev aria-label="تصویر قبلی" class="group absolute start-4 top-1/2 z-40 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-slate-700 shadow-sm backdrop-blur transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-white/50"><x-heroicon-o-chevron-right class="h-5 w-5" /></button>
                                <button type="button" data-carousel-next aria-label="تصویر بعدی" class="group absolute end-4 top-1/2 z-40 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-slate-700 shadow-sm backdrop-blur transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-white/50"><x-heroicon-o-chevron-left class="h-5 w-5" /></button>
                                <div class="absolute bottom-5 left-1/2 z-40 flex -translate-x-1/2 gap-2">
                                    @foreach ($listing->images as $image)
                                        <button type="button" data-carousel-slide-to="{{ $loop->index }}" aria-label="نمایش تصویر {{ $loop->iteration }}" class="h-2.5 w-2.5 rounded-full bg-white/60 transition hover:bg-white focus:outline-none focus:ring-2 focus:ring-white/70 {{ $loop->first ? 'bg-white' : '' }}"></button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="pointer-events-none absolute inset-x-5 bottom-5 z-20 flex items-end justify-between gap-3 text-white"><span class="rounded-full bg-slate-950/70 px-3 py-1.5 text-[10px] font-bold backdrop-blur">{{ \App\Support\PersianNumber::digits($listing->images->count()) }} تصویر</span><span class="rounded-full bg-slate-950/70 px-3 py-1.5 text-[10px] font-bold backdrop-blur">برای جابه‌جایی لمس کنید</span></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-lg bg-white p-5 shadow-sm border border-slate-200">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">{{ $listing->brand->name }}</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $listing->phoneModel->name_fa ?: $listing->phoneModel->name }}</span>
                    </div>
                    <h2 class="mt-3 text-2xl font-black leading-9 text-neutral">{{ $listing->title }}</h2>
                </div>
                @auth
                    <form method="POST" action="{{ route('listings.favorite.toggle', $listing) }}" class="shrink-0">
                        @csrf
                        <button type="submit" title="{{ $isFavorited ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' }}" class="rounded-lg {{ $isFavorited ? 'bg-primary text-white' : 'bg-primary-50 text-primary' }} p-3 transition hover:scale-105">
                            <x-heroicon-o-heart class="h-6 w-6" />
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" title="ورود برای ذخیره آگهی" class="shrink-0 rounded-lg bg-primary-50 p-3 text-primary transition hover:scale-105">
                        <x-heroicon-o-heart class="h-6 w-6" />
                    </a>
                @endauth
            </div>

            <div class="mt-6 flex items-end justify-between gap-3 rounded-lg bg-slate-50 p-4">
                <div>
                    <p class="text-[11px] font-bold text-slate-400">قیمت پیشنهادی</p>
                    <p class="mt-1 text-2xl font-black text-primary">{{ $listing->price_on_request ? 'تماس بگیرید' : \App\Support\PersianNumber::format($listing->price).' تومان' }}</p>
                </div>
                @if ($listing->is_negotiable)
                    <span class="inline-flex items-center gap-1 rounded-full bg-secondary/10 px-3 py-1.5 text-xs font-bold text-secondary">
                        <x-heroicon-o-arrow-path class="h-3.5 w-3.5" /> قابل مذاکره
                    </span>
                @endif
            </div>

            @if ($listing->description)
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <p class="mb-2 text-xs font-black text-neutral">توضیحات فروشنده</p>
                    <p class="text-sm leading-8 text-slate-600">{{ $listing->description }}</p>
                </div>
            @endif

            @auth
                <div class="mt-5 flex flex-wrap gap-2">
                    @if (auth()->user()->can('update', $listing))
                        <a href="{{ route('listings.edit', $listing) }}" class="inline-flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2.5 text-sm font-bold text-primary transition hover:bg-primary/15">
                            <x-heroicon-o-pencil-square class="h-4 w-4" /> ویرایش آگهی
                        </a>
                    @endif
                    @if (auth()->user()->can('markSold', $listing))
                        <form method="POST" action="{{ route('listings.sold', $listing) }}">
                            @csrf @method('PATCH')
                            <button class="inline-flex items-center gap-2 rounded-lg bg-secondary/10 px-4 py-2.5 text-sm font-bold text-success transition hover:bg-secondary/15">
                                <x-heroicon-o-check-badge class="h-4 w-4" /> علامت‌گذاری به‌عنوان فروخته‌شده
                            </button>
                        </form>
                    @endif
                </div>
            @endauth
        </div>

        <div class="rounded-lg bg-white p-5 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary"><x-heroicon-o-information-circle class="h-5 w-5" /></span>
                    <div><h3 class="font-black text-neutral">مشخصات دستگاه</h3><p class="text-[11px] text-slate-400">اطلاعات ثبت‌شده برای این محصول</p></div>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
                @forelse ($listing->attributeValues as $value)
                    <div class="rounded-lg bg-slate-50 p-3">
                        <span class="block text-[11px] text-slate-500">{{ $value->attribute->name }}</span>
                        @php
                            $displayValue = filled($value->value_json)
                                ? collect($value->value_json)->filter(fn ($item) => filled($item))->implode('، ')
                                : ($value->value_string ?? $value->value_integer ?? $value->value_decimal ?? ($value->value_boolean ? 'بله' : 'خیر'));
                            $displayValue = \App\Support\PersianNumber::digits($displayValue);
                        @endphp
                        <span class="mt-1 flex items-center gap-2 text-sm font-bold text-neutral">
                            @if ($value->attribute->slug === 'color' && filled($displayValue))
                                <span class="h-4 w-4 rounded-full border border-slate-300" style="background-color: {{ \App\Support\ColorPalette::hex((string) $displayValue) }}"></span>
                            @endif
                            <span>{{ $displayValue ?: 'ثبت نشده' }}</span>
                        </span>
                    </div>
                @empty
                    <p class="col-span-2 rounded-lg bg-slate-50 p-4 text-sm text-slate-500">مشخصات تکمیلی ثبت نشده است.</p>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-neutral p-5 text-white shadow-xl shadow-slate-900/10">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-lg font-black">{{ mb_substr($listing->user->name, 0, 1) }}</div>
                    <div><p class="text-xs text-white/60">فروشنده آگهی</p><p class="font-black">{{ $listing->user->name }}</p></div>
                </div>
                @if ($listing->user->storefront?->is_enabled)
                    <a href="{{ route('storefront.show', $listing->user->storefront) }}" class="rounded-full bg-primary px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-primary-600">مشاهده غرفه</a>
                @else
                    <span class="rounded-full bg-white/10 px-3 py-1 text-[10px] font-bold text-white/70">تماس امن</span>
                @endif
            </div>
            <div class="mt-5 border-t border-white/10 pt-4">
                @if ($contactRevealed)
                    <a href="tel:{{ $listing->user->mobile }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-secondary px-4 py-4 text-sm font-black text-white transition hover:bg-secondary/90"><x-heroicon-o-phone class="h-5 w-5" /> {{ $listing->user->mobile }}</a>
                @elseif (auth()->check())
                    <p class="mb-3 text-xs leading-6 text-white/60">برای حفظ امنیت، شماره تماس بعد از تأیید شماره موبایل شما نمایش داده می‌شود.</p>
                    <form method="POST" action="{{ route('listings.contact-otp', $listing) }}">@csrf<button class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3.5 text-sm font-black text-white transition hover:bg-primary-600"><x-heroicon-o-shield-check class="h-5 w-5" /> دریافت کد تأیید</button></form>
                    @if (session('contact_otp_listing_id') == $listing->id)
                        <form method="POST" action="{{ route('listings.contact-otp.verify', $listing) }}" class="mt-3 flex gap-2">@csrf<label for="listing-contact-otp" class="sr-only">کد پنج رقمی تأیید شماره تماس</label><input id="listing-contact-otp" name="otp" inputmode="numeric" maxlength="5" placeholder="کد ۵ رقمی" class="public-input min-w-0 flex-1 bg-white text-center"/><button class="rounded-lg bg-white px-4 py-3 text-sm font-black text-neutral focus:outline-none focus:ring-4 focus:ring-white/40">تأیید</button></form>
                        <p class="mt-2 text-[11px] text-white/50">کد ۵ دقیقه اعتبار دارد.</p>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-white px-4 py-3.5 text-sm font-black text-neutral"><x-heroicon-o-lock-closed class="h-5 w-5" /> ورود برای مشاهده شماره تماس</a>
                @endif
            </div>
        </div>

        @auth
            @if (! $listing->isOwnedBy(auth()->user()))
                <div id="listing-report-accordion" data-accordion="collapse" class="rounded-lg bg-white shadow-sm border border-slate-200">
                    <h2 id="listing-report-heading">
                        <button type="button" data-accordion-target="#listing-report-panel" aria-expanded="false" aria-controls="listing-report-panel" class="flex w-full items-center justify-between gap-3 p-5 text-right text-sm font-black text-neutral hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-primary/10">
                            <span class="flex items-center gap-2"><x-heroicon-o-flag class="h-5 w-5 text-error" /> گزارش مشکل در این آگهی</span>
                            <x-heroicon-o-chevron-down class="h-5 w-5 shrink-0 text-slate-400" />
                        </button>
                    </h2>
                    <div id="listing-report-panel" class="hidden" aria-labelledby="listing-report-heading">
                    <form method="POST" action="{{ route('listings.report', $listing) }}" class="space-y-3 border-t border-slate-100 p-5">@csrf
                        <label for="listing-report-reason" class="sr-only">دلیل گزارش</label><select id="listing-report-reason" name="reason" required class="public-select"><option value="">دلیل گزارش را انتخاب کنید</option><option value="اطلاعات نادرست">اطلاعات نادرست</option><option value="آگهی تکراری">آگهی تکراری</option><option value="محتوای نامناسب">محتوای نامناسب</option><option value="فروشنده مشکوک">فروشنده مشکوک</option></select>
                        <label for="listing-report-description" class="sr-only">توضیح تکمیلی گزارش</label><textarea id="listing-report-description" name="description" rows="3" placeholder="توضیح تکمیلی (اختیاری)" class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm text-neutral shadow-sm transition focus:border-primary focus:ring-2 focus:ring-primary/30"></textarea>
                        <button class="w-full rounded-lg bg-error px-4 py-3 text-sm font-bold text-white">ثبت گزارش</button>
                    </form>
                    </div>
                </div>
            @endif
        @endauth

        @if ($relatedListings->isNotEmpty())
            <div>
                <div class="mb-3 flex items-center justify-between"><div><p class="text-[11px] font-bold text-primary">پیشنهاد برای شما</p><h2 class="mt-1 text-lg font-black text-neutral">آگهی‌های مرتبط</h2></div><a href="{{ route('listings.index', ['brand_id' => $listing->brand_id, 'phone_model_id' => $listing->phone_model_id]) }}" class="text-xs font-bold text-primary">مشاهده همه</a></div>
                <div class="grid grid-cols-2 gap-3">@foreach ($relatedListings as $related)<x-listing-card :listing="$related" />@endforeach</div>
            </div>
        @endif
    </section>
</x-app-layout>
