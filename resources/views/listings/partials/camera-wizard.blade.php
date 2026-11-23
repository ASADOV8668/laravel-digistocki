<div class="flex min-h-0 flex-1 flex-col" x-cloak @pagehide.window="closeListingCamera()">
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <video x-ref="listingCameraVideo" x-show="cameraOpened" autoplay playsinline muted
            class="h-full w-full object-contain" aria-label="پیش‌نمایش زندهٔ دوربین"></video>
        <template x-if="!cameraOpened && previews.length">
            <img :src="previews[0].url" alt="عکس محصول" class="h-full w-full object-contain">
        </template>
        <div x-show="!cameraOpened && !previews.length" class="absolute inset-0 grid place-content-center justify-items-center gap-2 text-white">
            <x-heroicon-o-camera class="h-10 w-10" />
            <p x-text="cameraBusy ? 'در انتظار اجازهٔ دوربین…' : 'از محصول عکس بگیرید'"></p>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-transparent to-slate-950/70"></div>
    </div>
    <div class="relative flex items-center justify-between gap-3 p-4 text-white">
        <a href="{{ route('listings.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-950/80 px-3 py-2 text-sm font-bold">
            <x-heroicon-o-arrow-right class="h-4 w-4" /> ثبت آگهی دستی
        </a>
        <button type="button" x-show="previews.length && !cameraOpened" @click="openListingCamera()" :disabled="cameraBusy"
            class="rounded-xl bg-slate-950/80 px-3 py-2 text-sm font-bold">گرفتن دوباره عکس</button>
    </div>
    @if ($errors->any())
        <ul role="alert" class="relative mx-4 max-h-32 overflow-auto rounded-xl bg-red-50 p-3 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
    <p x-show="cameraError || invalid" x-text="cameraError || message" role="status"
        class="relative mx-4 rounded-xl bg-slate-950/85 p-3 text-sm text-white"></p>
    <input x-ref="listingCameraFile" type="file" name="images[]" hidden>
    <div x-show="cameraOpened || !previews.length" class="relative mt-auto flex flex-col items-center gap-3 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] text-center text-white">
        <button type="button" x-show="cameraOpened" @click="takeListingPicture()" :disabled="cameraBusy"
            class="rounded-full border-4 border-white/50 bg-white px-6 py-3 font-bold text-slate-900">گرفتن عکس</button>
        <button type="button" x-show="!cameraOpened && !cameraBusy" @click="openListingCamera()"
            class="rounded-full bg-primary px-6 py-3 font-bold text-white">باز کردن دوربین</button>
        <button type="button" x-show="cameraOpened && previews.length" @click="closeListingCamera()"
            class="rounded-xl bg-slate-950/80 px-4 py-2 text-sm">بازگشت به عکس قبلی</button>
    </div>

    <div x-show="previews.length && !cameraOpened" class="relative mt-auto max-h-[45dvh] w-full max-w-2xl space-y-3 self-center overflow-y-auto px-4 pb-3">
        <div x-show="brandId" class="flex gap-2 overflow-x-auto py-1" aria-label="مشخصات انتخاب‌شده">
            <button type="button" @click="cameraStep = 'brand'" :disabled="cameraStepBusy"
                class="shrink-0 rounded-full bg-slate-100 px-3 py-2 text-xs font-bold text-slate-800"
                x-text="fastBrandName || 'برند'"></button>
            <template x-for="item in cameraSummary(cameraDetailsStep === 'review')" :key="item.key">
                <button type="button" @click="cameraStep = item.key" :disabled="cameraStepBusy"
                    class="shrink-0 rounded-full bg-slate-100 px-3 py-2 text-xs font-bold text-slate-800 hover:bg-primary-50"
                    :aria-label="item.value !== '' ? 'ویرایش ' + item.label + '، ' + item.value : 'افزودن ' + item.label">
                    <bdi x-text="item.value !== '' ? item.value : '+ ' + item.label"></bdi>
                </button>
            </template>
        </div>
        <p x-show="cameraStepBusy" role="status" class="text-sm text-white">در حال دریافت گزینه‌ها…</p>
        <div x-show="cameraStep === 'brand'" class="rounded-2xl bg-white p-4 text-slate-900" x-transition:enter="motion-safe:transition motion-safe:duration-200"
            x-transition:enter-start="motion-safe:translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
            <h2 class="mb-2 text-sm font-bold">برند گوشی</h2>
            <div class="flex gap-2 overflow-x-auto p-1">
                @foreach ($brands as $brand)
                    <button type="button" @click="chooseCameraBrand(@js(['id' => $brand->id, 'label' => $brand->name_en ?: $brand->name]))"
                        :disabled="cameraStepBusy" :aria-pressed="String(brandId) === @js((string) $brand->id)"
                        :class="String(brandId) === @js((string) $brand->id) ? 'border-primary bg-primary-50' : 'border-slate-200 bg-white'"
                        class="min-w-24 shrink-0 rounded-xl border p-3 text-sm font-bold">
                        <img src="{{ $brand->featuredImageUrl() }}" alt="" class="mx-auto mb-2 h-10 w-10 object-contain">
                        <bdi>{{ $brand->name_en ?: $brand->name }}</bdi>
                    </button>
                @endforeach
            </div>
        </div>
        <template x-for="stage in fastStages()" :key="cameraStageKey(stage)">
            <div x-show="cameraStep === cameraStageKey(stage)" class="rounded-2xl bg-white p-4 text-slate-900" x-transition:enter="motion-safe:transition motion-safe:duration-200"
                x-transition:enter-start="motion-safe:translate-y-4 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
                <h2 class="mb-2 text-sm font-bold" x-text="fastStageLabel(stage)"></h2>
                <template x-if="cameraStep === cameraStageKey(stage) && stage.attribute?.type === 'multi_select'">
                    <div x-data="{ draft: Array.isArray(fastStageValue(stage)) ? [...fastStageValue(stage)] : [] }"
                        class="space-y-2">
                        <div class="flex gap-2 overflow-x-auto p-1">
                            <template x-for="option in cameraStageOptions(stage)" :key="option.id">
                                <label class="flex shrink-0 items-center gap-2 rounded-full border border-slate-200 px-3 py-2 text-sm">
                                    <input type="checkbox" x-model="draft" :value="option.id"><bdi x-text="option.label"></bdi>
                                </label>
                            </template>
                        </div>
                        <button type="button" @click="chooseCameraStage(stage, { id: draft })"
                            class="rounded-full bg-primary px-4 py-2 text-sm font-bold text-white">ادامه</button>
                    </div>
                </template>
                <div x-show="stage.attribute?.type !== 'multi_select'" class="flex gap-2 overflow-x-auto p-1">
                    <template x-for="option in cameraStageOptions(stage)" :key="option.id">
                        <button type="button" @click="chooseCameraStage(stage, option)" :disabled="cameraStepBusy"
                            :aria-pressed="String(fastStageValue(stage)) === String(option.id)"
                            :class="String(fastStageValue(stage)) === String(option.id) ? 'border-primary bg-primary-50 text-primary' : 'border-slate-200 bg-white'"
                            class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-full border px-4 py-2 text-sm font-bold">
                            <span x-show="stage.attribute?.slug === 'color'" class="h-4 w-4 rounded-full border border-slate-300"
                                :style="{backgroundColor: fastColorHex(option.label)}"></span>
                            <bdi x-text="stage.type === 'model' ? (option.secondary || option.label) : stage.attribute?.unit === '%' ? option.label + '٪' : option.label"></bdi>
                        </button>
                    </template>
                </div>
                <button type="button" x-show="!stage.required" @click="chooseCameraStage(stage, { id: '' })"
                    class="mt-2 rounded-full border border-slate-200 px-4 py-2 text-sm">رد کردن</button>
                <template x-if="cameraStep === cameraStageKey(stage) && !cameraStageOptions(stage).length && cameraStageAcceptsText(stage)">
                    <div class="flex gap-2">
                        <input :type="stage.attribute.type === 'string' ? 'text' : 'number'" :step="stage.attribute.type === 'decimal' ? 'any' : '1'"
                            :value="fastStageValue(stage)" :aria-label="fastStageLabel(stage)"
                            @keydown.enter.prevent="$el.reportValidity() && chooseCameraStage(stage, { id: $el.value.trim() })"
                            class="min-w-0 grow rounded-lg border-slate-300 text-sm">
                        <button type="button" @click="$el.previousElementSibling.reportValidity() && chooseCameraStage(stage, { id: $el.previousElementSibling.value.trim() })"
                            class="rounded-full bg-primary px-4 py-2 text-sm font-bold text-white">ادامه</button>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
