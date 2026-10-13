<x-admin-layout title="اعلان‌ها">
    <section class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold text-primary">مرکز پیگیری</p>
                <h1 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">اعلان‌های مدیریت</h1>
                <p class="mt-1 text-sm text-gray-400">فروشگاه‌ها و آگهی‌های منتظر بررسی را از اینجا دنبال کنید.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (auth()->user()->unreadNotifications()->exists())
                    <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf
                        @method('PATCH')<button
                            class="inline-flex items-center gap-2 rounded-xl bg-primary/10 px-4 py-2.5 text-xs font-bold text-primary hover:bg-primary/20"><x-heroicon-o-check
                                class="h-4 w-4" /> همه را خواندم</button></form>
                @endif
                @if (auth()->user()->notifications()->exists())
                    <form method="POST" action="{{ route('admin.notifications.destroy-all') }}">@csrf
                        @method('DELETE')<button
                            class="inline-flex items-center gap-2 rounded-xl bg-error/10 px-4 py-2.5 text-xs font-bold text-error hover:bg-error/20"
                            onclick="return confirm('همه اعلان‌های مدیریت حذف شوند؟')"><x-heroicon-o-trash
                                class="h-4 w-4" /> حذف همه</button></form>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-success/20 bg-success/10 p-4 text-sm font-bold text-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php($data = $notification->data)
                <article data-admin-notification-id="{{ $notification->id }}"
                    class="admin-card border-r-4 {{ $notification->read_at ? 'border-r-gray-200' : 'border-r-primary' }} p-5">
                    <div class="flex items-start gap-4">
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $notification->read_at ? 'bg-gray-100 text-gray-400' : 'bg-primary/10 text-primary' }}">
                            @if (($data['store_id'] ?? null) !== null)
                                <x-heroicon-o-building-storefront class="h-5 w-5" />
                            @else
                                <x-heroicon-o-rectangle-stack class="h-5 w-5" />
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="font-black text-gray-900 dark:text-white">{{ $data['title'] ?? 'اعلان سیستم' }}</h2>
                                @if (!$notification->read_at)
                                    <span class="rounded-full bg-primary px-2.5 py-1 text-[10px] font-black text-white">جدید</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm leading-7 text-gray-500 dark:text-gray-300">{{ $data['message'] ?? '' }}</p>
                            <p class="mt-2 text-[11px] text-gray-400">{{ \App\Support\PersianDate::human($notification->created_at) }}
                                ({{ \App\Support\PersianDate::format($notification->created_at) }})</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if (!empty($data['url']))
                            <a href="{{ $data['url'] }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-xs font-bold text-white hover:bg-primary-600">
                                بررسی مورد <x-heroicon-o-arrow-left class="h-4 w-4" />
                            </a>
                        @endif
                        @if (!$notification->read_at)
                            <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">@csrf
                                @method('PATCH')<button
                                    class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">علامت خوانده‌شده</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="admin-card p-12 text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-heroicon-o-bell
                            class="h-8 w-8" /></span>
                    <h2 class="mt-5 font-black text-gray-900 dark:text-white">اعلانی برای مدیریت وجود ندارد</h2>
                    <p class="mt-2 text-sm text-gray-400">اعلان فروشگاه‌ها و آگهی‌های نیازمند بررسی در این بخش نمایش داده می‌شود.</p>
                </div>
            @endforelse
        </div>

        {{ $notifications->links('vendor.pagination.flowbite') }}
    </section>
</x-admin-layout>
