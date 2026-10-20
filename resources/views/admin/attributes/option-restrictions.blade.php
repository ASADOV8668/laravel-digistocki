<x-admin-layout :title="'محدودسازی گزینه‌های '.$attribute->name">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.attributes.index') }}"
                class="rounded-xl bg-slate-100 p-2 text-slate-500 transition hover:bg-accent/10 hover:text-accent"
                aria-label="بازگشت به ویژگی‌ها">
                <x-heroicon-o-arrow-right class="h-5 w-5" />
            </a>
            <div>
                <p class="text-xs font-bold text-accent">ویژگی‌های گوشی</p>
                <h1 class="mt-1 text-xl font-black text-slate-900">محدودسازی گزینه‌های {{ $attribute->name }}</h1>
            </div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-6xl space-y-6">
        <x-admin.feedback />

        <div class="admin-card p-5">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black text-slate-900">افزودن شرط جدید</h2>
                    <p class="mt-1 text-xs leading-6 text-slate-400">
                        برای مدل‌های انتخاب‌شده فقط گزینه‌های انتخابی نمایش داده می‌شوند.
                    </p>
                </div>
                <span class="rounded-full bg-accent/10 px-3 py-1 text-[11px] font-bold text-accent">
                    {{ $attribute->type->value }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.attributes.option-restrictions.store', $attribute) }}"
                x-data="attributeOptionRestrictionForm(@js(route('admin.attributes.models')), @js($attribute->options ?? []))"
                class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                @csrf
                <div>
                    <label class="mb-2 block text-xs font-bold text-slate-600">مدل‌های گوشی</label>
                    <input x-model="query" type="search" placeholder="جستجوی برند یا مدل..."
                        class="mb-2 w-full rounded-xl border-slate-200 text-sm focus:border-accent focus:ring-accent">
                    <div x-show="loading" x-cloak class="mb-2 text-xs text-slate-400">در حال دریافت مدل‌ها...</div>
                    <select name="phone_model_ids[]" x-model="selectedModels" multiple size="7" required
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-accent focus:ring-accent">
                        <template x-for="model in models" :key="model.id">
                            <option :value="String(model.id)" x-text="model.label"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-[10px] text-slate-400">برای انتخاب چند مدل، Ctrl یا Command را نگه دارید.</p>
                </div>

                <div>
                    <label class="mb-2 block text-xs font-bold text-slate-600">گزینه‌های مجاز برای این مدل‌ها</label>
                    <select name="options[]" x-model="selectedOptions" multiple size="7" required
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-accent focus:ring-accent">
                        <template x-for="option in options" :key="option">
                            <option :value="option" x-text="option"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-[10px] text-slate-400">فقط گزینه‌هایی از همین ویژگی قابل انتخاب هستند.</p>
                </div>

                <button type="submit"
                    class="rounded-xl bg-accent px-5 py-3 text-xs font-bold text-white transition hover:bg-accent/90">
                    ذخیره شرط
                </button>
            </form>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-sm font-black text-slate-900">شرط‌های ثبت‌شده</h2>
                <p class="mt-1 text-xs text-slate-400">هر ردیف را می‌توانید مستقل حذف کنید.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($rules as $rule)
                    <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-center">
                        <div>
                            <p class="mb-2 text-[11px] font-bold text-slate-400">مدل‌ها</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($rule['models'] as $model)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                        {{ $model['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-[11px] font-bold text-slate-400">گزینه‌های مجاز</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($rule['options'] as $option)
                                    <span class="rounded-full bg-accent/10 px-2.5 py-1 text-[11px] font-bold text-accent">
                                        {{ $option }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <form method="POST"
                            action="{{ route('admin.attributes.option-restrictions.destroy', [$attribute, $rule['id']]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-error/10 px-4 py-2.5 text-xs font-bold text-error transition hover:bg-error/20">
                                <x-heroicon-o-trash class="h-4 w-4" />
                                حذف شرط
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-slate-400">هنوز شرطی برای این ویژگی ثبت نشده است.</div>
                @endforelse
            </div>
        </div>
    </section>
</x-admin-layout>
