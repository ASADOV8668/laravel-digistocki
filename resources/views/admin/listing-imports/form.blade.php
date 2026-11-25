<x-admin-layout :title="$parser->exists ? 'ویرایش قالب ایمپورت' : 'ایجاد قالب ایمپورت'">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-black text-slate-900">{{ $parser->exists ? 'ویرایش قالب ایمپورت' : 'ایجاد قالب ایمپورت' }}</h1>
            <a href="{{ route('admin.listing-imports.index', ['parser' => $parser->key]) }}" class="text-sm font-bold text-info">بازگشت به ایمپورت آگهی</a>
        </div>
    </x-slot>

    <form method="POST" action="{{ $parser->exists ? route('admin.listing-imports.update', $parser) : route('admin.listing-imports.store') }}" class="admin-card max-w-4xl space-y-5 p-5">
        @csrf
        @if ($parser->exists)
            @method('PUT')
        @endif

        @php
            $fields = [
                'title' => 'عنوان',
                'key' => 'شناسه قالب',
                'description' => 'توضیحات',
                'pattern' => 'الگوی خواندن هر آگهی',
                'sample_text' => 'نمونه لیست قیمت',
                'is_active' => 'وضعیت',
            ];
        @endphp

        @foreach ($fields as $field => $label)
            <div>
                <label for="{{ $field }}" class="mb-2 block text-sm font-bold text-slate-900">{{ $label }}</label>
                @if ($field === 'is_active')
                    <select name="{{ $field }}" id="{{ $field }}" class="rounded-xl border-slate-200">
                        <option value="1" @selected(old($field, $parser->is_active))>فعال</option>
                        <option value="0" @selected(!old($field, $parser->is_active))>غیرفعال</option>
                    </select>
                @elseif (in_array($field, ['title', 'key']))
                    <input required name="{{ $field }}" id="{{ $field }}" value="{{ old($field, $parser->$field) }}" @if ($field === 'key') dir="ltr" pattern="[a-z0-9][a-z0-9_\-]*" @endif class="w-full rounded-xl border-slate-200">
                @else
                    <textarea name="{{ $field }}" id="{{ $field }}" rows="{{ $field === 'sample_text' ? 8 : 3 }}" dir="{{ $field === 'pattern' ? 'ltr' : 'auto' }}" class="w-full rounded-xl border-slate-200">{{ old($field, $parser->$field) }}</textarea>
                @endif
                @if ($field === 'pattern')
                    <p class="mt-2 text-xs leading-6 text-slate-600">
                        متن ثابت هر آگهی را عیناً بنویسید. از <bdi>{model}</bdi> و <bdi>{price}</bdi> برای مدل و قیمت به تومان استفاده کنید.
                        <bdi>{color}</bdi>، <bdi>{battery}</bdi> و <bdi>{description}</bdi> اختیاری هستند.
                        @if ($parser->exists && $parser->class_name !== \App\Services\ListingImport\Parsers\PatternParser::class)
                            برای استفاده از الگوی پیش‌فرض این قالب، فیلد را خالی بگذارید.
                        @endif
                    </p>
                    <code dir="ltr" class="mt-1 block text-left text-xs text-slate-700">{model} | {color} | {battery}% | {price} تومان</code>
                @endif
                @error($field)
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <button type="submit" class="rounded-xl bg-info px-5 py-2.5 text-sm font-bold text-white">ذخیره قالب</button>
    </form>
</x-admin-layout>
