<x-admin-layout title="اصطلاحات">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-black text-slate-900">اصطلاحات</h1>
            <a href="{{ route('admin.listing-imports.index') }}" class="text-sm font-bold text-info">
                بازگشت به ایمپورت آگهی
            </a>
        </div>
    </x-slot>

    <section class="admin-card mb-6 p-5">
        <h2 class="mb-4 font-black text-slate-900">{{ $term ? 'ویرایش اصطلاح' : 'تعریف اصطلاح' }}</h2>
        <form method="POST" action="{{ $term ? route('admin.listing-imports.terms.update', $term) : route('admin.listing-imports.terms.store') }}"
            class="flex flex-wrap items-end gap-4">
            @csrf
            @if ($term)
                @method('PATCH')
            @endif
            <div class="min-w-48 flex-1">
                <label for="emoji" class="mb-2 block text-sm font-bold text-slate-700">اصطلاح در لیست قیمت</label>
                <input id="emoji" name="emoji" dir="auto" required placeholder="مثلاً nat یا 🟥"
                    value="{{ old('emoji', $term?->emoji) }}" class="w-full rounded-xl border-slate-200">
                @error('emoji') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-48 flex-1">
                <label for="meaning" class="mb-2 block text-sm font-bold text-slate-700">معنی فارسی</label>
                <input id="meaning" name="meaning" required maxlength="255" placeholder="مثلاً نات اکتیو"
                    value="{{ old('meaning', $term?->meaning) }}" class="w-full rounded-xl border-slate-200">
                @error('meaning') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-48 flex-1">
                <label for="target" class="mb-2 block text-sm font-bold text-slate-700">گزینه مرحله‌ساز در ایمپورت</label>
                @php
                    $selectedTarget = old('target', $term?->attribute_id
                        ? json_encode([$term->attribute_id, $term->attribute_value], JSON_UNESCAPED_UNICODE)
                        : '');
                @endphp
                <select id="target" name="target" class="w-full rounded-xl border-slate-200">
                    <option value="">بدون تکمیل خودکار</option>
                    @foreach ($targets as $group)
                        <optgroup label="{{ $group['label'] }}">
                            @foreach ($group['options'] as $option => $target)
                                <option value="{{ $target }}" @selected($selectedTarget === $target)>{{ $option }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('target') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded-xl bg-info px-4 py-2.5 text-sm font-bold text-white">
                {{ $term ? 'ذخیره تغییرات' : 'ثبت اصطلاح' }}
            </button>
            @if ($term)
                <a href="{{ route('admin.listing-imports.terms.index') }}" class="px-2 py-2.5 text-sm font-bold text-slate-600">انصراف</a>
            @endif
        </form>
    </section>

    @if (!$term)
        <section class="admin-card overflow-hidden">
            <h2 class="border-b border-slate-100 p-5 font-black text-slate-900">اصطلاحات تعریف‌شده</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($terms as $item)
                    <li class="flex flex-wrap items-center gap-4 px-5 py-4 text-sm">
                        <span class="max-w-full break-all font-bold" dir="auto">{{ $item->emoji }}</span>
                        <div class="min-w-0 flex-1 break-words text-slate-700">
                            <p>{{ $item->meaning }}</p>
                            @if ($item->attribute)
                                <p class="mt-1 text-xs text-slate-500">{{ $item->attribute->name }} ← {{ $item->attribute_value }}</p>
                            @endif
                        </div>
                        <a href="{{ route('admin.listing-imports.terms.edit', $item) }}" class="font-bold text-info">ویرایش</a>
                        <form method="POST" action="{{ route('admin.listing-imports.terms.destroy', $item) }}"
                            onsubmit="return confirm('این اصطلاح حذف شود؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-bold text-red-600">حذف</button>
                        </form>
                    </li>
                @empty
                    <li class="p-5 text-sm text-slate-500">هنوز اصطلاحی تعریف نشده است.</li>
                @endforelse
            </ul>
            @if ($terms->hasPages())
                <div class="border-t border-slate-100 p-5">{{ $terms->links() }}</div>
            @endif
        </section>
    @endif
</x-admin-layout>
