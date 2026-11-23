<div class="space-y-4" x-cloak @pagehide.window="closeListingCamera()">
    <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-slate-950">
        <video x-ref="listingCameraVideo" x-show="cameraOpened" autoplay playsinline muted
            class="h-full w-full object-contain" aria-label="پیش‌نمایش زندهٔ دوربین"></video>
        <template x-if="!cameraOpened && previews.length">
            <img :src="previews[0].url" alt="عکس محصول" class="h-full w-full object-contain">
        </template>
        <div x-show="!cameraOpened && !previews.length" class="absolute inset-0 grid place-content-center justify-items-center gap-2 text-white">
            <x-heroicon-o-camera class="h-10 w-10" />
            <p x-text="cameraBusy ? 'در انتظار اجازهٔ دوربین…' : 'از محصول عکس بگیرید'"></p>
        </div>
        <div x-show="cameraOpened" class="absolute inset-x-0 bottom-3 flex justify-center">
            <button type="button" @click="takeListingPicture()" :disabled="cameraBusy"
                class="rounded-full border-4 border-white/70 bg-white px-5 py-2 font-bold text-slate-900 shadow-lg">گرفتن عکس</button>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" x-show="!cameraOpened" @click="openListingCamera()" :disabled="cameraBusy"
            class="rounded-full border border-primary/20 px-4 py-2 text-sm font-bold text-primary"
            x-text="previews.length ? 'عکس دوباره' : 'باز کردن دوربین'"></button>
        <button type="button" x-show="cameraOpened" @click="closeListingCamera()"
            class="rounded-full border border-slate-200 px-4 py-2 text-sm">بستن دوربین</button>
        <label class="cursor-pointer rounded-full border border-slate-200 px-4 py-2 text-sm focus-within:ring-2 focus-within:ring-primary/30">
            انتخاب عکس از فایل‌ها
            <input x-ref="listingCameraFile" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                @change="useListingCameraFile($event)" class="sr-only">
        </label>
    </div>
    <p x-show="cameraError || invalid" x-text="cameraError || message" role="status" class="text-sm text-red-700"></p>

    <div x-show="previews.length && !cameraOpened" class="space-y-4">
        <div x-show="brandId" class="flex flex-wrap gap-2" aria-label="مشخصات انتخاب‌شده">
            <button type="button" @click="cameraStep = 'brand'" :disabled="cameraStepBusy"
                class="rounded-full bg-slate-100 px-3 py-2 text-xs font-bold text-slate-800"
                x-text="fastBrandName || 'برند'"></button>
            <template x-for="item in cameraSummary()" :key="item.key">
                <button type="button" @click="cameraStep = item.key" :disabled="cameraStepBusy"
                    class="rounded-full bg-slate-100 px-3 py-2 text-xs font-bold text-slate-800 hover:bg-primary-50"
                    :aria-label="'ویرایش ' + item.label + '، ' + item.value">
                    <bdi x-text="item.value"></bdi>
                </button>
            </template>
        </div>
        <p x-show="cameraStepBusy" role="status" class="text-sm text-slate-500">در حال دریافت گزینه‌ها…</p>
        <div x-show="cameraStep === 'brand'" x-transition:enter="motion-safe:transition motion-safe:duration-200"
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
            <div x-show="cameraStep === cameraStageKey(stage)" x-transition:enter="motion-safe:transition motion-safe:duration-200"
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
                            <bdi x-text="stage.type === 'model' ? (option.secondary || option.label) : option.label"></bdi>
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
