<x-app-layout title="جستجوهای ذخیره‌شده">
    <section class="mx-auto max-w-3xl space-y-4 px-4 py-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-black text-neutral">جستجوهای ذخیره‌شده</h1>
            <a href="{{ route('listings.index') }}" class="text-sm font-bold text-primary">جستجوی آگهی‌ها</a>
        </div>
        <p class="text-sm text-slate-500">وقتی آگهی مطابق جستجو منتشر شود یا قیمت آن کاهش پیدا کند، در سایت اعلان دریافت می‌کنید.</p>
        @if (session('status'))
            <p class="text-sm font-bold text-success" role="status">{{ session('status') }}</p>
        @endif
        <ul class="space-y-2">
            @forelse ($searches as $search)
                <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                    <a class="font-bold text-primary" href="{{ route('listings.index', ['q' => $search->query]) }}">{{ $search->query }}</a>
                    <form method="POST" action="{{ route('listings.searches.destroy', $search->id) }}">
                        @csrf
                        @method('DELETE')
                        <button class="text-sm font-bold text-error">حذف و غیرفعال‌کردن اعلان</button>
                    </form>
                </li>
            @empty
                <li class="text-sm text-slate-500">هنوز جستجویی ذخیره نکرده‌اید.</li>
            @endforelse
        </ul>
        {{ $searches->links() }}
    </section>
</x-app-layout>
