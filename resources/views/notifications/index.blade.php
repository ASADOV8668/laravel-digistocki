<x-app-layout title="اعلان‌ها">
    <x-slot name="header"><div class="flex items-center justify-between"><h1 class="text-xl font-black text-neutral">اعلان‌ها</h1>@if (auth()->user()->unreadNotifications()->exists())<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="rounded-xl bg-primary/10 px-3 py-2 text-xs font-bold text-primary">خواندن همه</button></form>@endif</div></x-slot>
    <section class="space-y-3 px-4 py-6">
        @forelse ($notifications as $notification)
            @php($data = $notification->data)
            <article class="rounded-2xl {{ $notification->read_at ? 'bg-white' : 'border border-primary/20 bg-primary-50' }} p-4 shadow-sm">
                <div class="flex items-start gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $notification->read_at ? 'bg-slate-100 text-slate-400' : 'bg-primary text-white' }}"><x-heroicon-o-bell class="h-5 w-5" /></span><div class="min-w-0 flex-1"><h2 class="font-black text-neutral">{{ $data['title'] ?? 'اعلان سیستم' }}</h2><p class="mt-1 text-sm leading-6 text-slate-600">{{ $data['message'] ?? '' }}</p>@if (! empty($data['reason']))<p class="mt-2 rounded-xl bg-white/70 p-2 text-xs text-slate-500">دلیل: {{ $data['reason'] }}</p>@endif<p class="mt-2 text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p></div></div>
                <div class="mt-3 flex items-center gap-2">@if (! empty($data['url']))<a href="{{ $data['url'] }}" class="rounded-lg bg-neutral px-3 py-2 text-xs font-bold text-white">مشاهده آگهی</a>@endif @if (! $notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600">علامت خوانده‌شده</button></form>@endif</div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">هنوز اعلانی ندارید.</div>
        @endforelse
        {{ $notifications->links() }}
    </section>
</x-app-layout>
