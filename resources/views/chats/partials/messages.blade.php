@forelse ($messages as $message)
    @php($isMine = $message->user_id === $currentUserId)
    <div class="flex {{ $isMine ? 'justify-start' : 'justify-end' }}" data-message-id="{{ $message->id }}">
        <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm {{ $isMine ? 'bg-primary text-white' : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100' }}">
            @if ($message->image_path)
                <a href="{{ asset('storage/'.$message->image_path) }}" target="_blank" rel="noopener"
                    class="mb-2 block overflow-hidden rounded-xl">
                    <img src="{{ asset('storage/'.$message->image_path) }}" alt="تصویر پیام" loading="lazy"
                        class="max-h-72 w-full object-cover">
                </a>
            @endif
            @if ($message->body)
                <p class="whitespace-pre-line break-words text-sm leading-7">{{ $message->body }}</p>
            @endif
            <p class="mt-1 text-[10px] {{ $isMine ? 'text-white/70' : 'text-slate-400 dark:text-slate-500' }}">
                {{ \App\Support\PersianNumber::digits(\App\Support\PersianDate::human($message->created_at)) }}
            </p>
        </div>
    </div>
@empty
    <div class="flex min-h-56 items-center justify-center rounded-2xl border border-dashed border-slate-200 text-sm text-slate-400 dark:border-slate-700">
        هنوز پیامی در این گفت‌وگو ارسال نشده است.
    </div>
@endforelse
