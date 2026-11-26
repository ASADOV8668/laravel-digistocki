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
            @if ($term)
                <div class="w-full">
                    <label for="candidate_lines" class="mb-2 block text-sm font-bold text-slate-700">رنگ‌های احتمالی، هر رنگ در یک خط</label>
                    <textarea id="candidate_lines" name="candidate_lines" rows="4" dir="auto"
                        class="w-full rounded-xl border-slate-200">{{ old('candidate_lines', implode(PHP_EOL, $term->color_candidates ?? [])) }}</textarea>
                    @error('candidate_lines') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
            <button type="submit" class="rounded-xl bg-info px-4 py-2.5 text-sm font-bold text-white">
                {{ $term ? 'ذخیره تغییرات' : 'ثبت اصطلاح' }}
            </button>
            @if ($term)
                <a href="{{ route('admin.listing-imports.terms.index') }}" class="px-2 py-2.5 text-sm font-bold text-slate-600">انصراف</a>
            @endif
        </form>
    </section>

    @if (!$term)
        <section class="admin-card mb-6 p-5">
            <h2 class="mb-4 font-black text-slate-900">ورود گروهی اصطلاحات</h2>
            <form method="POST" action="{{ route('admin.listing-imports.terms.bulk.store') }}" class="space-y-3"
                x-data="{ mode: @js(old('bulk_mode', 'colors')) }">
                @csrf
                <label for="bulk_mode" class="block text-sm font-bold text-slate-700">نوع لیست</label>
                <select id="bulk_mode" name="bulk_mode" x-model="mode" class="rounded-xl border-slate-200">
                    <option value="colors" @selected(old('bulk_mode', 'colors') === 'colors')>رنگ‌ها</option>
                    <option value="terms" @selected(old('bulk_mode') === 'terms')>لیست کلمه‌ای (فارسی و انگلیسی)</option>
                </select>
                <div x-show="mode === 'terms'" x-cloak>
                    <label for="bulk_attribute" class="mb-2 block text-sm font-bold text-slate-700">ویژگی مرحله‌ساز</label>
                    <select id="bulk_attribute" name="bulk_attribute" :disabled="mode !== 'terms'" class="rounded-xl border-slate-200">
                        <option value="">تشخیص خودکار از روی معنی</option>
                        @foreach ($targets as $group)
                            <option value="{{ $group['id'] }}" @selected((string) old('bulk_attribute') === (string) $group['id'])>{{ $group['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <label for="bulk_lines" class="block text-sm font-bold text-slate-700">هر خط را به شکل اصطلاح=مقدار وارد کنید. در لیست کلمه‌ای، اصطلاح ورودی می‌تواند فارسی یا انگلیسی باشد.</label>
                <textarea id="bulk_lines" name="bulk_lines" rows="5" dir="auto" required
                    :placeholder="mode === 'colors' ? '🟡=Yellow\n🟡=Gold\nblk=Black' : 'نات‌اکتیو=نات اکتیو\nفعال شده=اکتیو\nnat=نات اکتیو'"
                    class="w-full rounded-xl border-slate-200">{{ old('bulk_lines') }}</textarea>
                @error('bulk_lines') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @error('bulk_mode') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @error('bulk_attribute') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-xl bg-info px-4 py-2.5 text-sm font-bold text-white">ثبت لیست</button>
            </form>
        </section>
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
                            @if ($item->color_candidates)
                                <p class="mt-1 text-xs text-slate-500">{{ implode('، ', $item->color_candidates) }}</p>
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
