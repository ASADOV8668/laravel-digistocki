<div class="space-y-5 rounded-2xl border border-primary/15 bg-primary-50/40 p-4" data-fast-wizard>
    <div>
        <h2 class="text-lg font-black text-neutral">انتخاب سریع گوشی</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">برند، سری، مدل و ویژگی‌ها را با یک کلیک انتخاب کنید.</p>
    </div>

    <div>
        <p class="mb-2 text-sm font-bold text-neutral">۱. برند گوشی</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($brands as $brand)
                <button type="button" @click="selectFastBrand(@js(['id' => $brand->id, 'label' => $brand->name, 'secondary' => $brand->name_en, 'image' => $brand->featuredImageUrl()]))"
                    class="flex min-h-28 flex-col items-center justify-center gap-1 rounded-xl border bg-white p-2 text-center transition hover:border-primary/50 hover:shadow-sm"
                    :class="String(brandId) === '{{ $brand->id }}' ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200'">
                    <img src="{{ $brand->featuredImageUrl() }}" alt="{{ $brand->name }}" class="h-14 w-14 object-contain" loading="lazy">
                    <span class="text-xs font-black text-neutral">{{ $brand->name }}</span>
                    @if ($brand->name_en)<span class="text-[10px] text-slate-400" dir="ltr">{{ $brand->name_en }}</span>@endif
                </button>
            @endforeach
        </div>
    </div>

    <input type="hidden" name="brand_id" x-model="brandId">
    <input type="hidden" name="phone_series_id" x-model="seriesId">
    <input type="hidden" name="phone_model_id" x-model="modelId">

    <div x-show="brandId && fastLoading" x-cloak class="flex items-center gap-2 rounded-xl bg-white px-3 py-3 text-xs font-bold text-primary">
        <span class="h-4 w-4 animate-spin rounded-full border-2 border-primary/20 border-t-primary"></span>
        در حال آماده‌سازی مرحله بعد…
    </div>

    <template x-for="(stage, index) in fastStages()" :key="`fast-stage-${index}-${stage.type}-${stage.attribute?.id || ''}`">
        <div x-show="brandId && index <= fastStageIndex()" x-cloak class="rounded-xl border border-slate-200 bg-white p-3">
            <div class="mb-3 flex items-center justify-between gap-2">
                <p class="text-sm font-bold text-neutral" x-text="`${index + 2}. ${fastStageLabel(stage)}`"></p>
                <span x-show="stage.required" class="text-[10px] font-bold text-primary">الزامی</span>
            </div>
            <div class="flex gap-2" :class="stage.display_type === 'swiper' ? 'overflow-x-auto pb-1' : 'flex-wrap'">
                <template x-for="option in fastStageOptions(stage)" :key="option.id">
                    <button type="button" @click="selectFastStage(stage, option)"
                        class="shrink-0 rounded-xl border px-4 py-2.5 text-xs font-bold transition hover:border-primary/50 hover:bg-primary-50"
                        :class="String(fastStageValue(stage)) === String(option.id) ? 'border-primary bg-primary-50 text-primary ring-2 ring-primary/15' : 'border-slate-200 text-neutral'">
                        <span x-show="stage.type === 'attribute' && stage.attribute?.slug === 'color'" class="ml-1 inline-block h-3.5 w-3.5 rounded-full border border-slate-300 align-middle" :style="`background-color: ${listingColorHex?.[option.label] || '#94a3b8'}`"></span>
                        <span x-text="option.label"></span>
                        <span x-show="option.secondary && stage.type !== 'attribute'" class="mr-1 text-[10px] text-slate-400" dir="ltr" x-text="`(${option.secondary})`"></span>
                    </button>
                </template>
                <button type="button" x-show="!stage.required && stage.type === 'attribute'" @click="selectFastStage(stage, {id: '', label: ''})"
                    class="shrink-0 rounded-xl border border-dashed border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-500">همه موارد</button>
            </div>
            <p x-show="!fastStageOptions(stage).length" class="text-xs text-slate-400">برای این مرحله گزینه‌ای ثبت نشده است.</p>
        </div>
    </template>

    <div x-show="brandId && !fastLoading && !fastStages().length" x-cloak class="rounded-xl bg-warning/10 p-3 text-xs leading-6 text-neutral">
        برای این برند مرحله اختصاصی تعریف نشده است؛ مسیر پیش‌فرض فعال است.
    </div>
</div>
