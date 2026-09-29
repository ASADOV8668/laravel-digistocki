<x-admin-layout>
    <x-slot name="header"><h1 class="text-xl font-black text-neutral">مدیریت مدل‌های گوشی</h1></x-slot>
    <section class="space-y-4 px-4 py-6">
        <div class="grid grid-cols-2 gap-2"><a href="{{ route('admin.brands.index') }}" class="rounded-xl bg-white px-3 py-3 text-center text-xs font-bold text-neutral shadow-sm">برندها</a><a href="{{ route('admin.phone-models.index') }}" class="rounded-xl bg-accent px-3 py-3 text-center text-xs font-bold text-white">مدل‌ها</a></div>
        <form method="POST" action="{{ route('admin.phone-models.store') }}" class="space-y-2 rounded-2xl bg-white p-4 shadow-sm">
            @csrf
            <h2 class="font-bold text-neutral">افزودن مدل</h2>
            <select name="brand_id" required class="w-full rounded-xl border-slate-200 text-sm"><option value="">برند را انتخاب کنید</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name }} / {{ $brand->name_en }}</option>@endforeach</select>
            <input name="name" required placeholder="نام داخلی مدل" class="w-full rounded-xl border-slate-200 text-sm">
            <div class="grid grid-cols-2 gap-2"><input name="name_fa" placeholder="نام فارسی، مثل آیفون ۱۵ پرو" class="w-full rounded-xl border-slate-200 text-sm"><input name="name_en" placeholder="نام انگلیسی، مثل iPhone 15 Pro" dir="ltr" class="w-full rounded-xl border-slate-200 text-sm"></div>
            <input name="release_year" type="number" placeholder="سال عرضه" class="w-full rounded-xl border-slate-200 text-sm">
            <p class="text-xs font-bold text-slate-500">ویژگی‌های قابل نمایش</p>
            <div class="grid grid-cols-2 gap-2">@foreach ($attributes as $attribute)<label class="flex items-center gap-2 text-xs"><input type="checkbox" name="attribute_ids[]" value="{{ $attribute->id }}">{{ $attribute->name }}</label>@endforeach</div>
            <button class="w-full rounded-xl bg-neutral px-4 py-3 text-sm font-bold text-white">ذخیره مدل</button>
        </form>
        @forelse ($models as $model)
            <div class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm"><div><h2 class="font-bold text-neutral">{{ $model->brand->name }} {{ $model->name_fa ?: $model->name }}</h2><p class="mt-1 text-xs text-slate-500">{{ $model->name_en ?: $model->name }} · {{ $model->listings_count }} آگهی</p></div><form method="POST" action="{{ route('admin.phone-models.toggle', $model) }}">@csrf @method('PATCH')<button class="rounded-lg {{ $model->is_active ? 'bg-secondary/10 text-success' : 'bg-slate-100 text-slate-500' }} px-3 py-2 text-xs font-bold">{{ $model->is_active ? 'فعال' : 'غیرفعال' }}</button></form></div>
        @empty<p class="text-sm text-slate-500">مدلی وجود ندارد.</p>@endforelse
        {{ $models->links() }}
    </section>
</x-admin-layout>
