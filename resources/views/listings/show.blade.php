<x-app-layout title="جزئیات آگهی">
    <x-slot name="header"><div class="flex items-center gap-2"><a href="{{ route('listings.index') }}" class="rounded-xl p-2 text-slate-500"><x-heroicon-o-arrow-right class="h-5 w-5" /></a><h1 class="text-xl font-black text-neutral">جزئیات آگهی</h1></div></x-slot>
    <section class="space-y-5 px-4 py-6">
        @if (session('status'))<div class="rounded-xl bg-secondary/10 p-3 text-sm font-bold text-success">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="rounded-xl bg-error/10 p-3 text-sm font-bold text-error">{{ $errors->first() }}</div>@endif

        @if ($listing->images->isNotEmpty())
            <div class="grid grid-cols-2 gap-2">@foreach ($listing->images as $image)<img src="{{ asset('storage/'.$image->path) }}" alt="{{ $listing->title }}" class="h-44 w-full rounded-2xl object-cover first:col-span-2 first:h-64">@endforeach</div>
        @else
            <div class="flex h-64 items-center justify-center rounded-3xl bg-gradient-to-br from-primary-50 to-accent/10 text-primary"><x-heroicon-o-device-phone-mobile class="h-24 w-24" /></div>
        @endif

        <div class="rounded-3xl bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4"><div><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">{{ $listing->brand->name }}</span><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $listing->phoneModel->name_fa ?: $listing->phoneModel->name }}</span></div><h2 class="mt-3 text-2xl font-black leading-9 text-neutral">{{ $listing->title }}</h2></div>@auth<form method="POST" action="{{ route('listings.favorite.toggle', $listing) }}">@csrf<button type="submit" title="{{ $isFavorited ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' }}" class="rounded-xl {{ $isFavorited ? 'bg-primary text-white' : 'bg-primary-50 text-primary' }} p-2"><x-heroicon-o-heart class="h-6 w-6" /></button></form>@else<a href="{{ route('login') }}" title="ورود برای ذخیره آگهی" class="rounded-xl bg-primary-50 p-2 text-primary"><x-heroicon-o-heart class="h-6 w-6" /></a>@endauth</div>
            <div class="mt-5 flex items-end justify-between"><p class="text-2xl font-black text-primary">{{ number_format($listing->price) }} <span class="text-xs font-bold">تومان</span></p><span class="text-xs text-slate-400">{{ number_format($listing->views_count) }} بازدید</span></div>
            @if ($listing->is_negotiable)<span class="mt-3 inline-flex rounded-full bg-secondary/10 px-3 py-1 text-xs font-bold text-secondary">قابل مذاکره</span>@endif
            @if ($listing->description)<p class="mt-5 border-t border-slate-100 pt-4 text-sm leading-8 text-slate-600">{{ $listing->description }}</p>@endif
            @auth
                @if (auth()->user()->can('update', $listing))<a href="{{ route('listings.edit', $listing) }}" class="mt-4 inline-flex rounded-xl bg-primary/10 px-4 py-2 text-sm font-bold text-primary">ویرایش آگهی</a>@endif
                @if (auth()->user()->can('markSold', $listing))<form method="POST" action="{{ route('listings.sold', $listing) }}" class="mt-3">@csrf @method('PATCH')<button class="rounded-xl bg-secondary/10 px-4 py-2 text-sm font-bold text-success">علامت‌گذاری به‌عنوان فروخته‌شده</button></form>@endif
            @endauth
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-sm"><h3 class="flex items-center gap-2 font-black text-neutral"><x-heroicon-o-information-circle class="h-5 w-5 text-primary" /> مشخصات دستگاه</h3><div class="mt-3 grid grid-cols-2 gap-2">@forelse ($listing->attributeValues as $value)<div class="rounded-xl bg-slate-50 p-3"><span class="block text-xs text-slate-500">{{ $value->attribute->name }}</span><span class="mt-1 block text-sm font-bold text-neutral">{{ $value->value_string ?? $value->value_integer ?? $value->value_decimal ?? ($value->value_boolean ? 'بله' : 'خیر') }}</span></div>@empty<p class="col-span-2 text-sm text-slate-500">مشخصات تکمیلی ثبت نشده است.</p>@endforelse</div></div>

        <div class="rounded-3xl bg-neutral p-5 text-white shadow-sm"><div class="flex items-center gap-3"><div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary text-lg font-black">{{ mb_substr($listing->user->name, 0, 1) }}</div><div><p class="text-xs text-white/60">فروشنده</p><p class="font-bold">{{ $listing->user->name }}</p></div></div><div class="mt-4 border-t border-white/10 pt-4">
            @if ($contactRevealed)
                <a href="tel:{{ $listing->user->mobile }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-4 text-sm font-bold text-white"><x-heroicon-o-phone class="h-5 w-5" /> {{ $listing->user->mobile }}</a>
            @elseif (auth()->check())
                <p class="mb-3 text-xs leading-6 text-white/60">برای حفظ امنیت، شماره تماس بعد از تأیید شماره موبایل شما نمایش داده می‌شود.</p>
                <form method="POST" action="{{ route('listings.contact-otp', $listing) }}">@csrf<button class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white"><x-heroicon-o-shield-check class="h-5 w-5" /> دریافت کد تأیید</button></form>
                @if (session('contact_otp_listing_id') == $listing->id)<form method="POST" action="{{ route('listings.contact-otp.verify', $listing) }}" class="mt-3 flex gap-2">@csrf<input name="otp" inputmode="numeric" maxlength="6" placeholder="کد ۶ رقمی" class="min-w-0 flex-1 rounded-xl border-0 text-center text-sm text-neutral"><button class="rounded-xl bg-white px-4 py-3 text-sm font-bold text-neutral">تأیید</button></form><p class="mt-2 text-[11px] text-white/50">کد ۵ دقیقه اعتبار دارد.</p>@endif
            @else
                <a href="{{ route('login') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-neutral"><x-heroicon-o-lock-closed class="h-5 w-5" /> ورود برای مشاهده شماره تماس</a>
            @endif
        </div></div>

        @auth
            @if (! $listing->isOwnedBy(auth()->user()))
                <details class="rounded-3xl bg-white p-5 shadow-sm">
                    <summary class="cursor-pointer list-none text-sm font-black text-neutral"><span class="flex items-center gap-2"><x-heroicon-o-flag class="h-5 w-5 text-error" /> گزارش مشکل در این آگهی</span></summary>
                    <form method="POST" action="{{ route('listings.report', $listing) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">@csrf
                        <select name="reason" required class="w-full rounded-xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary"><option value="">دلیل گزارش را انتخاب کنید</option><option value="اطلاعات نادرست">اطلاعات نادرست</option><option value="آگهی تکراری">آگهی تکراری</option><option value="محتوای نامناسب">محتوای نامناسب</option><option value="فروشنده مشکوک">فروشنده مشکوک</option></select>
                        <textarea name="description" rows="3" placeholder="توضیح تکمیلی (اختیاری)" class="w-full rounded-xl border-0 bg-slate-50 text-sm ring-1 ring-slate-100 focus:ring-primary"></textarea>
                        <button class="w-full rounded-xl bg-error px-4 py-3 text-sm font-bold text-white">ثبت گزارش</button>
                    </form>
                </details>
            @endif
        @endauth

        @if ($relatedListings->isNotEmpty())<div><div class="mb-3 flex items-center justify-between"><h2 class="text-lg font-black text-neutral">آگهی‌های مرتبط</h2><a href="{{ route('listings.index', ['brand_id' => $listing->brand_id, 'phone_model_id' => $listing->phone_model_id]) }}" class="text-xs font-bold text-primary">مشاهده همه</a></div><div class="grid grid-cols-2 gap-3">@foreach ($relatedListings as $related)<x-listing-card :listing="$related" />@endforeach</div></div>@endif
    </section>
</x-app-layout>
