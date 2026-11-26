<x-admin-layout title="درخواست‌های حذف استان">
    <x-slot name="header">
        <h2 class="text-xl font-black text-slate-900">درخواست‌های حذف استان</h2>
    </x-slot>
    <section class="space-y-4">
        <x-admin.feedback />
        @forelse ($requests as $item)
            <article class="admin-card space-y-3 p-5">
                <h3 class="font-black">{{ $item->province_name }} · {{ match ($item->status) {
                    'pending' => 'در انتظار تأیید',
                    'approved' => 'تأیید و حذف شد',
                    'rejected' => 'رد شد',
                    'blocked' => 'حذف مسدود شد',
                } }}</h3>
                <p class="text-sm">درخواست‌دهنده: {{ $item->requester?->name ?? '#'.$item->requested_by }} · {{ \App\Support\PersianDate::format($item->created_at) }}</p>
                <p class="whitespace-pre-wrap break-words text-sm">{{ $item->reason }}</p>
                @if ($item->status === 'pending' && $item->province)
                    <p class="text-sm">شهرها: {{ $item->province->cities_count }} · آگهی‌ها: {{ $item->province->listings_count }} · فروشگاه‌ها: {{ $item->province->stores_count }}</p>
                    @if ($item->province->name !== $item->province_name)
                        <p class="text-sm text-error">نام فعلی استان: {{ $item->province->name }}</p>
                    @endif
                @endif
                @if ($item->reviewed_at)
                    <p class="text-sm">بررسی‌کننده: {{ $item->reviewer?->name ?? '#'.$item->reviewed_by }} · {{ \App\Support\PersianDate::format($item->reviewed_at) }}</p>
                @elseif (auth()->user()->isSeniorAdmin() && (int) $item->requested_by !== (int) auth()->id())
                    <form method="POST" action="{{ route('admin.locations.deletion-requests.review', $item) }}" class="flex flex-wrap gap-2">
                        @csrf
                        @method('PATCH')
                        <button name="decision" value="approve" class="rounded-lg bg-error px-4 py-2 text-sm font-bold text-white"
                            onclick="return confirm('درخواست حذف استان تأیید شود؟')">تأیید حذف</button>
                        <button name="decision" value="reject" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold">رد درخواست</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="admin-card p-5 text-sm">درخواستی ثبت نشده است.</p>
        @endforelse
        {{ $requests->links() }}
    </section>
</x-admin-layout>
