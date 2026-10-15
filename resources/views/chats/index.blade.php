<x-app-layout title="چت‌ها">
    <section class="mx-auto max-w-3xl space-y-5 px-4 pb-28 pt-5 sm:px-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold text-primary">ارتباط با فروشندگان</p>
                <h1 class="mt-1 text-2xl font-black text-neutral dark:text-white">چت‌های من</h1>
            </div>
            <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">
                {{ \App\Support\PersianNumber::digits($conversations->total()) }} گفت‌وگو
            </span>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            @forelse ($conversations as $conversation)
                @php
                    $listing = $conversation->listing;
                    $other = $conversation->buyer_id === auth()->id() ? $conversation->seller : $conversation->buyer;
                    $image = $listing?->primaryImage?->thumbnail_path ?: $listing?->primaryImage?->path;
                @endphp
                <a href="{{ route('chats.show', $conversation) }}"
                    class="flex items-center gap-3 border-b border-slate-100 p-4 transition last:border-b-0 hover:bg-primary-50/60 dark:border-slate-800 dark:hover:bg-slate-800">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
                        <img src="{{ $image ? asset('storage/'.$image) : asset('images/listing-placeholder.svg') }}"
                            alt="" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="truncate font-black text-neutral dark:text-white">{{ $listing?->title ?? 'آگهی حذف‌شده' }}</h2>
                            @if ($conversation->latestMessage)
                                <time class="shrink-0 text-[10px] text-slate-400">{{ \App\Support\PersianNumber::digits(\App\Support\PersianDate::human($conversation->latestMessage->created_at)) }}</time>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">
                            {{ $other?->storefront?->name ?: ($other?->name ?: 'فروشنده') }}
                        </p>
                        <p class="mt-2 truncate text-sm text-slate-500 dark:text-slate-400">
                            {{ $conversation->latestMessage?->body ?: ($conversation->latestMessage ? 'تصویر ارسال شد' : 'شروع گفت‌وگو') }}
                        </p>
                    </div>
                    <x-heroicon-o-chevron-left class="h-5 w-5 shrink-0 text-slate-400" />
                </a>
            @empty
                <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <x-heroicon-o-chat-bubble-left-right class="h-8 w-8" />
                    </span>
                    <h2 class="mt-5 text-lg font-black text-neutral dark:text-white">هنوز گفت‌وگویی ندارید</h2>
                    <p class="mt-2 max-w-sm text-sm leading-7 text-slate-500 dark:text-slate-400">از صفحه هر آگهی می‌توانید با فروشنده گفت‌وگو را شروع کنید.</p>
                </div>
            @endforelse
        </div>

        @if ($conversations->hasPages())
            <div>{{ $conversations->links() }}</div>
        @endif
    </section>
</x-app-layout>
