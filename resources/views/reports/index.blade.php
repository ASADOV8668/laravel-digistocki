<x-app-layout title="گزارش‌های من">
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold text-primary">پیگیری درخواست‌ها</p>
                <h1 class="mt-1 text-xl font-black text-neutral">گزارش‌های من</h1>
            </div><span
                class="rounded-full bg-primary/10 px-3 py-1.5 text-xs font-black text-primary">{{ number_format($reports->total()) }}
                گزارش</span>
        </div>
    </x-slot>
    <section class="space-y-3 px-4 py-6">
        @forelse ($reports as $report)
            @php($status = ['pending' => ['در انتظار بررسی', 'bg-warning/10 text-warning'], 'reviewed' => ['در حال بررسی', 'bg-info/10 text-info'], 'resolved' => ['رسیدگی‌شده', 'bg-success/10 text-success'], 'rejected' => ['ردشده', 'bg-error/10 text-error']][$report->status] ?? ['نامشخص', 'bg-slate-100 text-slate-500'])
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-primary">{{ $report->reason }}</p>
                        <h2 class="mt-1 truncate font-black text-neutral dark:text-white">
                            {{ $report->listing?->title ?? 'آگهی حذف‌شده' }}</h2>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black {{ $status[1] }}">{{ $status[0] }}</span>
                        <form method="POST" action="{{ route('reports.destroy', $report) }}"
                            onsubmit="return confirm('این گزارش حذف شود؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" aria-label="حذف گزارش"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-error/10 hover:text-error focus:outline-none focus:ring-2 focus:ring-error/30 dark:text-slate-500"
                                title="حذف گزارش">
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </div>
                @if ($report->description)
                    <p class="mt-3 rounded-lg bg-slate-50 p-3 text-xs leading-6 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $report->description }}</p>
                @endif
                <p class="mt-3 text-[11px] text-slate-400">ثبت‌شده
                    {{ \App\Support\PersianDate::human($report->created_at) }}
                    ({{ \App\Support\PersianDate::format($report->created_at) }})</p>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <x-heroicon-o-flag class="mx-auto h-12 w-12 text-primary/30" />
                <h2 class="mt-4 font-black text-neutral dark:text-white">هنوز گزارشی ثبت نکرده‌اید</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">گزارش‌های مربوط به آگهی‌ها و وضعیت رسیدگی آن‌ها اینجا
                    نمایش داده می‌شود.</p><a href="{{ route('listings.index') }}"
                    class="mt-4 inline-flex rounded-lg bg-primary px-4 py-2.5 text-xs font-bold text-white">مشاهده
                    آگهی‌ها</a>
            </div>
        @endforelse
        {{ $reports->links('vendor.pagination.flowbite') }}
    </section>
</x-app-layout>
