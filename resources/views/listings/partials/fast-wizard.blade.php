<div class="space-y-5 rounded-2xl border border-primary/15 bg-primary-50/40 p-4" data-fast-wizard>
    <div>
        <h2 class="text-lg font-black text-neutral">انتخاب سریع گوشی</h2>
        <p class="mt-1 text-xs leading-6 text-slate-500">برند، سری، مدل و ویژگی‌ها را با یک کلیک انتخاب کنید.</p>
    </div>

    <div>
        <p class="mb-2 text-sm font-bold text-neutral">۱. برند گوشی</p>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-3 xl:grid-cols-4">
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
            <div x-init="stage.display_type === 'swiper' && $nextTick(() => centerFastSwiper($el, stage, fastStageValue(stage)))"
                @scroll.passive="stage.display_type === 'swiper' && syncFastSwiper($el, stage)"
                class="flex gap-2"
                :class="stage.display_type === 'swiper' ? 'min-h-16 flex-nowrap snap-x snap-mandatory items-center overflow-x-auto overscroll-x-contain pt-2 pb-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden' : 'flex-wrap'"
                :style="stage.display_type === 'swiper' ? 'padding-inline: calc(50% - 2.5rem); padding-block: .5rem .75rem; scroll-padding-inline: 50%;' : ''">
                <template x-for="(option, optionIndex) in fastStageOptions(stage)" :key="option.id">
                    <button type="button" @click="selectFastStage(stage, option); stage.display_type === 'swiper' && centerFastSwiper($event.currentTarget.parentElement, stage, option.id)"
                        :data-fast-swiper-option="stage.display_type === 'swiper' ? 'true' : null"
                        :data-fast-swiper-index="optionIndex"
                        :data-fast-swiper-value="String(option.id)"
                        class="shrink-0 snap-center rounded-xl border px-4 py-2.5 text-xs font-bold transition hover:border-primary/50 hover:bg-primary-50"
                        :class="stage.display_type === 'swiper'
                            ? (fastSwiperIsFocused(stage, option) ? 'min-w-20 border-primary bg-primary-50 text-primary opacity-100 shadow-sm ring-2 ring-primary/15' : 'min-w-20 border-slate-200 text-neutral opacity-50')
                            : (String(fastStageValue(stage)) === String(option.id) ? 'border-primary bg-primary-50 text-primary ring-2 ring-primary/15' : 'border-slate-200 text-neutral')">
                        <template x-if="stage.type === 'attribute' && stage.attribute?.slug === 'color'">
                            <span class="ml-1 inline-block h-3.5 w-3.5 rounded-full border border-slate-300 align-middle" :style="`background-color: ${fastColorHex(option.label)}`"></span>
                        </template>
                        <span x-text="option.label"></span>
                        <span x-show="option.secondary && stage.type !== 'attribute'" class="mr-1 text-[10px] text-slate-400" dir="ltr" x-text="`(${option.secondary})`"></span>
                    </button>
                </template>
                <button type="button" x-show="stage.display_type !== 'swiper' && !stage.required && stage.type === 'attribute' && stage.attribute?.slug !== 'ram'" @click="selectFastStage(stage, {id: '', label: ''})"
                    class="shrink-0 rounded-xl border border-dashed border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-500">همه موارد</button>
            </div>
            <p x-show="!fastStageOptions(stage).length" class="text-xs text-slate-400">برای این مرحله گزینه‌ای ثبت نشده است.</p>
        </div>
    </template>

    <div x-show="brandId && !fastLoading && !fastStages().length" x-cloak class="rounded-xl bg-warning/10 p-3 text-xs leading-6 text-neutral">
        برای این برند مرحله اختصاصی تعریف نشده است؛ مسیر پیش‌فرض فعال است.
    </div>
</div>
