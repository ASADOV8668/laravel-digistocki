<x-admin-layout title="مدیریت گزارش‌ها">
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-warning/10 text-warning"><x-heroicon-o-flag class="h-6 w-6" /></span>
                <div><h2 class="text-xl font-black text-slate-900">مدیریت گزارش‌ها</h2><p class="mt-1 text-xs text-slate-400">رسیدگی به گزارش‌های کاربران و وضعیت تخلف آگهی‌ها</p></div>
            </div>
            <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center"><strong class="block text-lg font-black text-slate-900">{{ number_format($reports->total()) }}</strong><span class="text-[11px] font-bold text-slate-500">گزارش در نتایج</span></div>
        </div>
    </x-slot>

    @php($statusLabels = ['pending' => 'در انتظار', 'reviewed' => 'بررسی‌شده', 'resolved' => 'حل‌شده', 'rejected' => 'ردشده'])

    <section class="space-y-6">
        @if (session('status'))
            <div class="flex items-center gap-2 rounded-2xl border border-success/15 bg-success/10 px-4 py-3 text-sm font-bold text-success" role="status"><x-heroicon-o-check-circle class="h-5 w-5 shrink-0" />{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="admin-card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md"><span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-warning/10 text-warning"><x-heroicon-o-clock class="h-5 w-5" /></span><span><strong class="block text-lg font-black text-slate-900">{{ number_format((int) ($reportStats['pending'] ?? 0)) }}</strong><span class="text-[11px] font-bold text-slate-500">در انتظار رسیدگی</span></span></a>
            <a href="{{ route('admin.reports.index', ['status' => 'reviewed']) }}" class="admin-card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md"><span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-info/10 text-info"><x-heroicon-o-eye class="h-5 w-5" /></span><span><strong class="block text-lg font-black text-slate-900">{{ number_format((int) ($reportStats['reviewed'] ?? 0)) }}</strong><span class="text-[11px] font-bold text-slate-500">بررسی‌شده</span></span></a>
            <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}" class="admin-card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md"><span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-success/10 text-success"><x-heroicon-o-check-badge class="h-5 w-5" /></span><span><strong class="block text-lg font-black text-slate-900">{{ number_format((int) ($reportStats['resolved'] ?? 0)) }}</strong><span class="text-[11px] font-bold text-slate-500">حل‌شده</span></span></a>
            <a href="{{ route('admin.reports.index', ['status' => 'rejected']) }}" class="admin-card flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:shadow-md"><span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-error/10 text-error"><x-heroicon-o-x-circle class="h-5 w-5" /></span><span><strong class="block text-lg font-black text-slate-900">{{ number_format((int) ($reportStats['rejected'] ?? 0)) }}</strong><span class="text-[11px] font-bold text-slate-500">ردشده</span></span></a>
        </div>

        <div class="admin-card p-5"><div class="mb-4 flex items-center gap-2"><x-heroicon-o-funnel class="h-5 w-5 text-warning" /><div><h3 class="text-sm font-black text-slate-900">فیلتر گزارش‌ها</h3><p class="mt-1 text-[11px] text-slate-400">گزارش‌ها را بر اساس وضعیت و تاریخ شمسی مشاهده کنید.</p></div></div><form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_180px_180px_auto]"><select name="status" class="rounded-xl border-slate-200 py-3 text-sm focus:border-warning focus:ring-warning"><option value="">همه وضعیت‌ها</option>@foreach ($statusLabels as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select><div><label for="admin-reports-date-from" class="sr-only">از تاریخ گزارش</label><input id="admin-reports-date-from" data-persian-datepicker name="date_from" value="{{ request('date_from') }}" placeholder="از تاریخ" autocomplete="off" class="w-full rounded-xl border-slate-200 py-3 text-sm focus:border-warning focus:ring-warning"></div><div><label for="admin-reports-date-to" class="sr-only">تا تاریخ گزارش</label><input id="admin-reports-date-to" data-persian-datepicker name="date_to" value="{{ request('date_to') }}" placeholder="تا تاریخ" autocomplete="off" class="w-full rounded-xl border-slate-200 py-3 text-sm focus:border-warning focus:ring-warning"></div><div class="flex gap-2"><button class="flex-1 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-warning">اعمال فیلتر</button>@if (request()->hasAny(['status', 'date_from', 'date_to']))<a href="{{ route('admin.reports.index') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-200">پاک‌کردن</a>@endif</div></form></div>

        <div class="admin-card overflow-hidden">
            <div class="hidden overflow-x-auto lg:block"><table class="min-w-full text-right text-sm"><thead class="border-b border-slate-100 bg-slate-50 text-xs font-black text-slate-500"><tr><th class="px-5 py-4">موضوع گزارش</th><th class="px-5 py-4">آگهی و گزارش‌دهنده</th><th class="px-5 py-4">توضیحات</th><th class="px-5 py-4">وضعیت</th><th class="px-5 py-4 text-left">به‌روزرسانی</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($reports as $report)
                    @php($reportClass = match ($report->status) { 'resolved' => 'bg-success/10 text-success', 'rejected' => 'bg-error/10 text-error', 'reviewed' => 'bg-info/10 text-info', default => 'bg-warning/10 text-warning' })
                    @php($reportOptions = $reportStatusOptions[$report->id] ?? [$report->status => $statusLabels[$report->status] ?? $report->status])
                    <tr class="transition hover:bg-slate-50"><td class="px-5 py-4"><p class="font-black text-slate-800">{{ $report->reason }}</p><p class="mt-1 text-xs text-slate-400">#{{ $report->id }}</p></td><td class="px-5 py-4"><p class="font-bold text-slate-700">{{ $report->listing?->title ?? 'آگهی حذف‌شده' }}</p><p class="mt-1 text-xs text-slate-400">{{ $report->user->name }}</p></td><td class="max-w-[260px] px-5 py-4"><p class="truncate text-xs text-slate-500" title="{{ $report->description }}">{{ $report->description ?: 'بدون توضیحات تکمیلی' }}</p></td><td class="px-5 py-4"><span class="rounded-full px-3 py-1.5 text-[11px] font-black {{ $reportClass }}">{{ $statusLabels[$report->status] ?? $report->status }}</span></td><td class="px-5 py-4">
                        @if (count($reportOptions) > 1)
                            <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="flex min-w-[210px] justify-end gap-2">@csrf @method('PATCH')<select name="status" class="rounded-lg border-slate-200 text-xs">@foreach ($reportOptions as $key => $label)<option value="{{ $key }}" @selected($report->status === $key)>{{ $label }}</option>@endforeach</select><button class="rounded-lg bg-info px-3 py-2 text-xs font-bold text-white transition hover:bg-info/80">ذخیره</button></form>
                        @else
                            <span class="text-xs font-bold text-slate-400">وضعیت نهایی</span>
                        @endif
                    </td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400">گزارشی پیدا نشد.</td></tr>
                @endforelse
            </tbody></table></div>
            <div class="divide-y divide-slate-100 lg:hidden">
                @forelse ($reports as $report)
                    @php($reportClass = match ($report->status) { 'resolved' => 'bg-success/10 text-success', 'rejected' => 'bg-error/10 text-error', 'reviewed' => 'bg-info/10 text-info', default => 'bg-warning/10 text-warning' })
                    @php($reportOptions = $reportStatusOptions[$report->id] ?? [$report->status => $statusLabels[$report->status] ?? $report->status])
                    <article class="p-5"><div class="flex items-start justify-between gap-3"><div><h2 class="font-black text-slate-800">{{ $report->reason }}</h2><p class="mt-1 text-xs text-slate-400">{{ $report->listing?->title ?? 'آگهی حذف‌شده' }} · {{ $report->user->name }}</p></div><span class="shrink-0 rounded-full px-2 py-1 text-[10px] font-black {{ $reportClass }}">{{ $statusLabels[$report->status] ?? $report->status }}</span></div>@if ($report->description)<p class="mt-3 rounded-xl bg-slate-50 p-3 text-xs leading-6 text-slate-600">{{ $report->description }}</p>@endif
                        @if (count($reportOptions) > 1)
                            <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="mt-4 flex gap-2">@csrf @method('PATCH')<select name="status" class="min-w-0 flex-1 rounded-lg border-slate-200 text-xs">@foreach ($reportOptions as $key => $label)<option value="{{ $key }}" @selected($report->status === $key)>{{ $label }}</option>@endforeach</select><button class="rounded-lg bg-info px-3 py-2 text-xs font-bold text-white">ذخیره</button></form>
                        @else
                            <p class="mt-4 text-xs font-bold text-slate-400">وضعیت نهایی</p>
                        @endif
                    </article>
                @empty
                    <div class="p-10 text-center text-sm text-slate-400">گزارشی پیدا نشد.</div>
                @endforelse
            </div>
        </div>
        {{ $reports->links() }}
    </section>
</x-admin-layout>
