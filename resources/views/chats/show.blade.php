@push('scripts')
    <script>
        (() => {
            const room = document.querySelector('[data-chat-room]');
            if (!room) return;

            const messages = document.getElementById('chat-messages');
            const form = document.getElementById('chat-send-form');
            const error = document.getElementById('chat-error');
            const image = document.getElementById('chat-image');
            const imageName = document.getElementById('chat-image-name');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            let polling = true;

            const scrollToBottom = () => {
                messages.scrollTop = messages.scrollHeight;
            };

            const showError = (message) => {
                error.textContent = message;
                error.classList.remove('hidden');
            };

            const refresh = async () => {
                if (!polling || document.hidden) return;
                try {
                    const response = await fetch(room.dataset.chatMessagesUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    const wasNearBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 120;
                    messages.innerHTML = payload.html;
                    if (wasNearBottom) scrollToBottom();
                } catch (_) {}
            };

            image.addEventListener('change', () => {
                imageName.textContent = image.files[0]?.name || '';
                imageName.classList.toggle('hidden', !image.files.length);
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                error.classList.add('hidden');
                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
                        credentials: 'same-origin',
                    });
                    const payload = await response.json();
                    if (!response.ok) {
                        showError(payload.message || Object.values(payload.errors || {}).flat()[0] || 'ارسال پیام انجام نشد.');
                        return;
                    }
                    messages.innerHTML = payload.html;
                    form.reset();
                    imageName.classList.add('hidden');
                    scrollToBottom();
                } catch (_) {
                    showError('ارتباط با سامانه برقرار نشد. دوباره تلاش کنید.');
                } finally {
                    button.disabled = false;
                }
            });

            scrollToBottom();
            const timer = window.setInterval(refresh, 1000);
            window.addEventListener('pagehide', () => {
                polling = false;
                window.clearInterval(timer);
            }, { once: true });
        })();
    </script>
@endpush

<x-app-layout title="چت با فروشنده">
    <section class="mx-auto max-w-3xl px-4 pb-28 pt-5 sm:px-6" dir="rtl"
        data-chat-room
        data-chat-messages-url="{{ route('chats.messages.index', $conversation) }}"
        data-chat-send-url="{{ route('chats.messages.store', $conversation) }}">
        @php
            $listing = $conversation->listing;
            $imageRecord = $listing?->primaryImage ?: $listing?->images?->first();
            $image = $imageRecord?->thumbnail_path ?: $imageRecord?->path;
            $seller = $conversation->seller;
        @endphp
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                <a href="{{ $listing ? route('listings.show', $listing) : route('chats.index') }}"
                    class="flex min-w-0 flex-1 items-center gap-3 rounded-xl p-1 transition hover:bg-slate-50 dark:hover:bg-slate-800">
                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
                        <img src="{{ $image ? asset('storage/'.$image) : asset('images/listing-placeholder.svg') }}"
                            alt="" class="h-full w-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold text-primary">موضوع گفت‌وگو</p>
                        <h1 class="truncate text-base font-black text-neutral dark:text-white">{{ $listing?->title ?? 'آگهی حذف‌شده' }}</h1>
                        <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $seller?->storefront?->name ?: ($seller?->name ?: 'فروشنده') }}</p>
                    </div>
                </a>
                <a href="{{ route('chats.index') }}" aria-label="بازگشت به چت‌ها"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-primary-50 hover:text-primary dark:bg-slate-800 dark:text-slate-300">
                    <x-heroicon-o-chevron-left class="h-5 w-5" />
                </a>
            </div>

            <div id="chat-messages" class="max-h-[calc(100vh-19rem)] min-h-80 space-y-3 overflow-y-auto bg-slate-50 p-4 dark:bg-slate-950/50">
                @include('chats.partials.messages', ['messages' => $messages, 'currentUserId' => $currentUserId])
            </div>

            <form id="chat-send-form" method="POST" action="{{ route('chats.messages.store', $conversation) }}" enctype="multipart/form-data"
                class="border-t border-slate-100 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                @csrf
                <div class="flex items-end gap-2">
                    <label for="chat-image" aria-label="ارسال تصویر"
                        class="inline-flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition hover:bg-primary-50 hover:text-primary dark:bg-slate-800 dark:text-slate-300">
                        <x-heroicon-o-photo class="h-5 w-5" />
                        <input id="chat-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" class="hidden">
                    </label>
                    <div class="min-w-0 flex-1">
                        <label for="chat-body" class="sr-only">متن پیام</label>
                        <textarea id="chat-body" name="body" rows="2" maxlength="5000" placeholder="پیام خود را بنویسید..."
                            class="block max-h-40 min-h-20 w-full resize-y overflow-y-auto rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-800 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            dir="rtl"></textarea>
                        <p id="chat-image-name" class="mt-1 hidden text-[10px] text-slate-400"></p>
                    </div>
                    <button type="submit" aria-label="ارسال پیام"
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-white shadow-sm transition hover:bg-primary-600 focus:outline-none focus:ring-4 focus:ring-primary/20">
                        <x-heroicon-o-paper-airplane class="h-5 w-5 -rotate-90" />
                    </button>
                </div>
                <p class="mt-2 text-[10px] text-slate-400">ارسال تصویر تا حداکثر {{ \App\Support\PersianNumber::digits(app(\App\Services\SystemOptions::class)->maxImageUploadMb()) }} مگابایت مجاز است.</p>
                <p id="chat-error" class="mt-2 hidden text-xs font-bold text-error"></p>
            </form>
        </div>
    </section>
</x-app-layout>
