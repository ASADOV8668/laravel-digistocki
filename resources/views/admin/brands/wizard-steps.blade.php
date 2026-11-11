<x-admin-layout title="مرحله‌ساز برند">
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-black text-slate-900">مرحله‌ساز {{ $brand->name }}</h2>
            <p class="mt-1 text-xs text-slate-400">ترتیب و نوع نمایش مراحل فرم ثبت آگهی این برند را تعیین کنید.</p>
        </div>
    </x-slot>
    <section class="space-y-6">
        @php($hasCustomSteps = $brand->wizardSteps->isNotEmpty())
        @php($steps = $hasCustomSteps ? $brand->wizardSteps : $defaultSteps)
        <x-admin.feedback />
        <div class="rounded-2xl border border-accent/20 bg-accent/5 p-4 text-xs leading-7 text-slate-600">
            مرحله‌ها بعد از انتخاب برند به‌صورت خودکار و با یک کلیک نمایش داده می‌شوند. برای گزینه‌های زیاد، حالت Swiper حرکت افقی فعال می‌شود.
            @if (!$hasCustomSteps)
                <span class="font-black text-accent">این فهرست، مسیر پیش‌فرض فعال برای این برند است.</span>
                <form method="POST" action="{{ route('admin.brands.wizard-steps.materialize', $brand) }}" class="mt-3" onsubmit="return confirm('مراحل پیش‌فرض به مراحل قابل ویرایش تبدیل شوند؟')">
                    @csrf
                    <button class="rounded-lg bg-accent px-3 py-2 text-xs font-bold text-white hover:bg-accent/90">فعال‌سازی ویرایش ترتیب و حذف مراحل</button>
                </form>
            @endif
        </div>
        <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
            <form method="POST" action="{{ route('admin.brands.wizard-steps.store', $brand) }}" class="admin-card h-fit space-y-4 p-5">
                @csrf
                <h3 class="text-sm font-black text-slate-900">افزودن مرحله</h3>
                <select name="step_type" required class="w-full rounded-xl border-slate-200 text-sm">
                    <option value="attribute">ویژگی</option>
                    <option value="series">سری گوشی</option>
                    <option value="model">مدل گوشی</option>
                </select>
                <select name="attribute_id" class="w-full rounded-xl border-slate-200 text-sm">
                    <option value="">ویژگی را انتخاب کنید</option>
                    @foreach ($attributes as $attribute)<option value="{{ $attribute->id }}">{{ $attribute->name }}</option>@endforeach
                </select>
                <select name="display_type" required class="w-full rounded-xl border-slate-200 text-sm">
                    <option value="buttons">دکمه‌ای</option>
                    <option value="swiper">Swiper افقی</option>
                </select>
                <input name="sort_order" type="number" min="1" value="1" required placeholder="اولویت مرحله" class="w-full rounded-xl border-slate-200 text-sm">
                <label class="flex items-center gap-2 text-xs font-bold text-slate-600"><input type="checkbox" name="is_required" value="1" checked> مرحله الزامی باشد</label>
                <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white hover:bg-accent">افزودن مرحله</button>
            </form>
            <div class="admin-card overflow-hidden">
                <div class="border-b border-slate-100 p-5"><h3 class="text-sm font-black text-slate-900">{{ $hasCustomSteps ? 'مراحل اختصاصی فعلی' : 'مراحل پیش‌فرض فعال' }}</h3></div>
                @if ($hasCustomSteps)
                    <form id="wizard-reorder-form" method="POST" action="{{ route('admin.brands.wizard-steps.reorder', $brand) }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 p-4">
                        @csrf @method('PATCH')
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <x-heroicon-o-bars-3 class="h-4 w-4 text-accent" />
                            <span>برای جابه‌جایی، هر مرحله را بکشید و در جای جدید رها کنید.</span>
                        </div>
                        @foreach ($steps as $step)
                            <input type="hidden" name="sort_orders[{{ $step->id }}]" value="{{ $step->sort_order }}" data-step-order-input="{{ $step->id }}">
                        @endforeach
                        <button class="rounded-lg bg-accent px-3 py-2 text-xs font-bold text-white hover:bg-accent/90">ذخیره ترتیب مراحل</button>
                    </form>
                @endif
                <div data-wizard-sortable-list class="divide-y divide-slate-100">
                    @forelse ($steps as $step)
                        <div class="p-5 transition hover:bg-slate-50 {{ $hasCustomSteps ? 'cursor-grab active:cursor-grabbing' : '' }}"
                            @if ($hasCustomSteps) draggable="true" data-wizard-step-row data-step-id="{{ $step->id }}" @endif>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-800"><span class="text-accent">مرحله <span data-step-number>{{ $step->sort_order }}</span> ·</span>
                                        {{ $step->step_type === 'attribute' ? ($step->attribute?->name ?: 'ویژگی حذف‌شده') : ($step->step_type === 'series' ? 'انتخاب سری' : 'انتخاب مدل') }}</p>
                                    <p class="mt-1 text-xs text-slate-400">{{ $step->display_type === 'swiper' ? 'Swiper افقی' : 'دکمه‌ای' }} · {{ $step->is_required ? 'الزامی' : 'اختیاری' }} @if (!$hasCustomSteps) · پیش‌فرض @endif</p>
                                </div>
                                @if ($hasCustomSteps)
                                    <form method="POST" action="{{ route('admin.brands.wizard-steps.destroy', [$brand, $step]) }}" onsubmit="return confirm('این مرحله حذف شود؟')">
                                        @csrf @method('DELETE')<button class="rounded-lg p-2 text-error hover:bg-error/10"><x-heroicon-o-trash class="h-5 w-5" /></button>
                                    </form>
                                @endif
                            </div>
                            @if ($hasCustomSteps)
                                <form method="POST" action="{{ route('admin.brands.wizard-steps.update', [$brand, $step]) }}" class="mt-3 flex flex-wrap items-center gap-2">
                                    @csrf @method('PATCH')
                                    <input name="sort_order" type="number" min="1" value="{{ $step->sort_order }}" class="w-24 rounded-lg border-slate-200 text-xs">
                                    <select name="display_type" class="rounded-lg border-slate-200 text-xs"><option value="buttons" @selected($step->display_type === 'buttons')>دکمه‌ای</option><option value="swiper" @selected($step->display_type === 'swiper')>Swiper</option></select>
                                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_required" value="1" @checked($step->is_required)> الزامی</label>
                                    <button class="rounded-lg bg-accent px-3 py-2 text-xs font-bold text-white">به‌روزرسانی</button>
                                </form>
                            @else
                                <p class="mt-3 text-xs leading-6 text-slate-400">این مراحل از ساختار پیش‌فرض کاتالوگ خوانده می‌شوند. برای سفارشی‌سازی، مرحله‌ی اختصاصی اضافه کنید.</p>
                            @endif
                        </div>
                    @empty
                        <div class="p-10 text-center text-sm text-slate-400">برای این برند مرحله اختصاصی تعریف نشده است؛ فرم پیش‌فرض استفاده می‌شود.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
    @if ($hasCustomSteps)
        <script>
            (() => {
                const list = document.querySelector('[data-wizard-sortable-list]');
                const reorderForm = document.getElementById('wizard-reorder-form');
                if (!list || !reorderForm) return;

                let dragged = null;
                const rows = () => [...list.querySelectorAll('[data-wizard-step-row]')];
                const syncOrder = () => rows().forEach((row, index) => {
                    const order = index + 1;
                    row.querySelector('[data-step-number]').textContent = order;
                    const input = reorderForm.querySelector('[data-step-order-input="' + row.dataset.stepId + '"]');
                    if (input) input.value = order;
                });

                rows().forEach((row) => {
                    row.addEventListener('dragstart', (event) => {
                        dragged = row;
                        row.classList.add('opacity-50');
                        event.dataTransfer.effectAllowed = 'move';
                    });
                    row.addEventListener('dragend', () => {
                        row.classList.remove('opacity-50');
                        dragged = null;
                    });
                    row.addEventListener('dragover', (event) => event.preventDefault());
                    row.addEventListener('drop', (event) => {
                        event.preventDefault();
                        if (!dragged || dragged === row) return;
                        const rect = row.getBoundingClientRect();
                        const after = event.clientY > rect.top + rect.height / 2;
                        list.insertBefore(dragged, after ? row.nextSibling : row);
                        syncOrder();
                    });
                });
            })();
        </script>
    @endif
</x-admin-layout>
