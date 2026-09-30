<x-admin-layout title="مدیریت آگهی‌ها">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary"><x-heroicon-o-rectangle-stack class="h-6 w-6" /></span>
                <div><h2 class="text-xl font-black text-slate-900">مدیریت آگهی‌ها</h2><p class="mt-1 text-xs text-slate-400">بررسی، تأیید و کنترل چرخه انتشار آگهی‌ها</p></div>
            </div>
            <div class="flex items-center gap-3"><div class="rounded-2xl bg-slate-100 px-4 py-3 text-center"><strong class="block text-lg font-black text-slate-900">{{ number_format($listings->total()) }}</strong><span class="text-[11px] font-bold text-slate-500">آگهی در نتایج</span></div><a href="{{ route('admin.listings.create') }}" class="inline-flex items-center gap-2 rounded-2xl bg-primary px-4 py-3 text-sm font-black text-white shadow-sm transition hover:bg-primary/90"><x-heroicon-o-plus class="h-5 w-5" />افزودن آگهی</a></div>
        </div>
    </x-slot>

    <section class="space-y-6">
        @if (session('status'))
            <div class="flex items-center gap-2 rounded-2xl border border-success/15 bg-success/10 px-4 py-3 text-sm font-bold text-success" role="status">
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />
                {{ session('status') }}
            </div>
        @endif

        @php
            $statusCards = [
                ['key' => 'pending', 'label' => 'در انتظار بررسی', 'icon' => 'heroicon-o-clock', 'class' => 'bg-warning/10 text-warning'],
                ['key' => 'approved', 'label' => 'منتشرشده', 'icon' => 'heroicon-o-check-badge', 'class' => 'bg-success/10 text-success'],
                ['key' => 'rejected', 'label' => 'ردشده', 'icon' => 'heroicon-o-x-circle', 'class' => 'bg-error/10 text-error'],
                ['key' => 'sold', 'label' => 'فروخته‌شده', 'icon' => 'heroicon-o-shopping-bag', 'class' => 'bg-info/10 text-info'],
                ['key' => 'expired', 'label' => 'منقضی‌شده', 'icon' => 'heroicon-o-clock', 'class' => 'bg-slate-100 text-slate-500'],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-5">
            @foreach ($statusCards as $card)
                <a href="{{ route('admin.listings.index', ['status' => $card['key']]) }}" class="admin-card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl {{ $card['class'] }}"><x-dynamic-component :component="$card['icon']" class="h-5 w-5" /></span>
                    <span><strong class="block text-lg font-black text-slate-900">{{ number_format((int) ($statusCounts[$card['key']] ?? 0)) }}</strong><span class="text-[11px] font-bold text-slate-500">{{ $card['label'] }}</span></span>
                </a>
            @endforeach
        </div>

        <div class="admin-card p-5">
            <div class="mb-4 flex items-center gap-2"><x-heroicon-o-funnel class="h-5 w-5 text-primary" /><div><h3 class="text-sm font-black text-slate-900">فیلتر و جستجو</h3><p class="mt-1 text-[11px] text-slate-400">آگهی موردنظر را بر اساس عنوان، کاربر یا وضعیت پیدا کنید.</p></div></div>
            <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto]">
                <div class="relative"><label for="admin-listings-search" class="sr-only">جستجوی آگهی، کاربر، برند یا مدل</label><x-heroicon-o-magnifying-glass class="pointer-events-none absolute right-3 top-3 h-5 w-5 text-slate-400" /><input id="admin-listings-search" name="q" value="{{ request('q') }}" placeholder="عنوان، کاربر، برند یا مدل" class="w-full rounded-xl border-slate-200 py-3 pr-10 text-sm focus:border-primary focus:ring-primary"></div>
                <select name="status" class="rounded-xl border-slate-200 py-3 text-sm focus:border-primary focus:ring-primary"><option value="">همه وضعیت‌ها</option>@foreach ($statuses as $item)<option value="{{ $item }}" @selected($status === $item)>{{ \App\Enums\ListingStatus::from($item)->label() }}</option>@endforeach</select>
                <div class="flex gap-2"><button class="flex-1 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-primary">اعمال فیلتر</button>@if (request()->hasAny(['q', 'status']))<a href="{{ route('admin.listings.index') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-200">پاک‌کردن</a>@endif</div>
            </form>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full text-right text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-black text-slate-500"><tr><th class="px-5 py-4">آگهی</th><th class="px-5 py-4">ثبت‌کننده</th><th class="px-5 py-4">وضعیت</th><th class="px-5 py-4">آمار</th><th class="px-5 py-4 text-left">عملیات</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($listings as $listing)
                        @php($statusClass = match ($listing->status->value) { 'approved' => 'bg-success/10 text-success', 'rejected' => 'bg-error/10 text-error', 'pending' => 'bg-warning/10 text-warning', 'sold' => 'bg-info/10 text-info', default => 'bg-slate-100 text-slate-500' })
                        <tr class="transition hover:bg-slate-50"><td class="px-5 py-4"><div class="flex min-w-[250px] items-center gap-3">@if ($listing->primaryImage)<img src="{{ asset('storage/'.$listing->primaryImage->path) }}" alt="{{ $listing->title }}" class="h-12 w-12 rounded-xl object-cover">@else<span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400"><x-heroicon-o-photo class="h-5 w-5" /></span>@endif<div><p class="font-black text-slate-800">{{ $listing->title }}</p><p class="mt-1 text-xs text-slate-400">{{ $listing->brand->name }} {{ $listing->phoneModel->name }}</p></div></div></td><td class="px-5 py-4"><p class="font-bold text-slate-700">{{ $listing->user->name }}</p><p class="mt-1 text-xs text-slate-400">{{ $listing->user->mobile ?: $listing->user->email }}</p></td><td class="px-5 py-4"><span class="rounded-full px-3 py-1.5 text-[11px] font-black {{ $statusClass }}">{{ $listing->status->label() }}</span></td><td class="px-5 py-4"><p class="font-bold text-slate-700">{{ number_format($listing->price) }} <span class="text-[10px] font-medium text-slate-400">تومان</span></p><p class="mt-1 text-xs text-slate-400">{{ number_format($listing->views_count) }} بازدید</p></td><td class="px-5 py-4"><div class="flex min-w-[250px] flex-wrap justify-end gap-2">@if (in_array($listing->status->value, ['pending', 'rejected'], true))<form method="POST" action="{{ route('admin.listings.approve', $listing) }}">@csrf @method('PATCH')<button class="rounded-lg bg-success px-3 py-2 text-xs font-bold text-white transition hover:bg-success/80">تأیید</button></form>@endif @if (in_array($listing->status->value, ['pending', 'approved'], true))<form method="POST" action="{{ route('admin.listings.reject', $listing) }}" class="flex gap-1"><input name="rejection_reason" required minlength="5" placeholder="دلیل رد" class="w-32 rounded-lg border-slate-200 text-xs">@csrf @method('PATCH')<button class="rounded-lg bg-error px-3 py-2 text-xs font-bold text-white transition hover:bg-error/80">رد</button></form>@endif</div></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400">آگهی‌ای با این فیلتر پیدا نشد.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="divide-y divide-slate-100 lg:hidden">
                @forelse ($listings as $listing)
                    @php($statusClass = match ($listing->status->value) { 'approved' => 'bg-success/10 text-success', 'rejected' => 'bg-error/10 text-error', 'pending' => 'bg-warning/10 text-warning', 'sold' => 'bg-info/10 text-info', default => 'bg-slate-100 text-slate-500' })
                    <article class="p-5"><div class="flex gap-3">@if ($listing->primaryImage)<img src="{{ asset('storage/'.$listing->primaryImage->path) }}" alt="{{ $listing->title }}" class="h-14 w-14 shrink-0 rounded-xl object-cover">@else<span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400"><x-heroicon-o-photo class="h-5 w-5" /></span>@endif<div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-2"><h2 class="font-black text-slate-800">{{ $listing->title }}</h2><span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-black {{ $statusClass }}">{{ $listing->status->label() }}</span></div><p class="mt-1 text-xs text-slate-400">{{ $listing->user->name }} · {{ $listing->brand->name }} {{ $listing->phoneModel->name }}</p><p class="mt-2 text-xs font-bold text-slate-600">{{ number_format($listing->price) }} تومان · {{ number_format($listing->views_count) }} بازدید</p></div></div><div class="mt-4 flex gap-2">@if (in_array($listing->status->value, ['pending', 'rejected'], true))<form method="POST" action="{{ route('admin.listings.approve', $listing) }}">@csrf @method('PATCH')<button class="rounded-lg bg-success px-3 py-2 text-xs font-bold text-white">تأیید</button></form>@endif @if (in_array($listing->status->value, ['pending', 'approved'], true))<form method="POST" action="{{ route('admin.listings.reject', $listing) }}" class="flex min-w-0 flex-1 gap-1"><input name="rejection_reason" required minlength="5" placeholder="دلیل رد" class="min-w-0 flex-1 rounded-lg border-slate-200 text-xs">@csrf @method('PATCH')<button class="rounded-lg bg-error px-3 py-2 text-xs font-bold text-white">رد</button></form>@endif</div></article>
                @empty
                    <div class="p-10 text-center text-sm text-slate-400">آگهی‌ای با این فیلتر پیدا نشد.</div>
                @endforelse
            </div>
        </div>
        {{ $listings->links() }}
    </section>
</x-admin-layout>
