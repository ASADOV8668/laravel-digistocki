<div class="mb-4 flex items-center justify-between rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-slate-100"><div><h2 class="text-sm font-black text-neutral">آگهی‌های تأییدشده</h2><p class="mt-1 text-[11px] text-slate-400">مرتب‌سازی بر اساس {{ ['newest' => 'جدیدترین', 'price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین', 'views' => 'پربازدیدترین'][$sort] }}</p></div><span class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-black text-slate-600">{{ number_format($listings->total()) }} نتیجه</span></div>
@if ($listings->count())
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 xl:grid-cols-3">@foreach ($listings as $listing)<x-listing-card :listing="$listing" />@endforeach</div>
    <div class="listing-pagination mt-5">{{ $listings->links() }}</div>
@else
    <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary"><x-heroicon-o-magnifying-glass class="h-7 w-7" /></span><h3 class="mt-4 font-black text-neutral">نتیجه‌ای پیدا نشد</h3><p class="mt-2 text-xs leading-6 text-slate-500">فیلترها را تغییر دهید یا عبارت جستجو را ساده‌تر کنید.</p><a href="{{ route('listings.index') }}" class="mt-4 inline-flex rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white">پاک کردن فیلترها</a></div>
@endif
