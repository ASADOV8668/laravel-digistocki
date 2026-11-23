<div x-show="cameraSpecsComplete()" x-cloak
    x-transition:enter="motion-safe:transition motion-safe:duration-200"
    x-transition:enter-start="motion-safe:translate-y-4 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    class="relative max-h-[50dvh] w-full max-w-2xl shrink-0 self-center overflow-y-auto rounded-t-3xl bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] text-slate-900"
    x-data="{ priceOnRequest: @js($allowContactPrice && (bool) old('price_on_request')), negotiable: @js((bool) old('is_negotiable')) }">
    <input type="hidden" name="is_negotiable" :value="negotiable ? '1' : '0'">
    @if ($allowContactPrice)
        <input type="hidden" name="price_on_request" :value="priceOnRequest ? '1' : '0'">
    @endif

    <div x-show="cameraDetailsStep === 'title'" class="listing-camera-step space-y-3">
        <label for="public-listing-title" class="block text-sm font-bold">عنوان آگهی</label>
        <input id="public-listing-title" name="title" value="{{ old('title') }}"
            data-auto-listing-title="{{ old('title', '') === '' ? 'true' : 'false' }}"
            x-ref="cameraTitle" type="text" required minlength="3" maxlength="160"
            @keydown.enter.prevent="$el.reportValidity() && finishCameraDetail('price')"
            class="public-input w-full">
        <button type="button" @click="$refs.cameraTitle.reportValidity() && finishCameraDetail('price')"
            class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white"
            x-text="cameraEditingDetail ? 'ذخیره تغییر' : 'ادامه'"></button>
    </div>

    <div x-show="cameraDetailsStep === 'price'" class="listing-camera-step space-y-3" x-data="moneyInput(@js(old('price')))">
        <label for="camera-listing-price" class="block text-sm font-bold">قیمت (تومان)</label>
        <input id="camera-listing-price" x-ref="cameraPrice" type="text" inputmode="numeric" dir="ltr"
            x-model="display" @input="formatInput($event); priceOnRequest = false"
            :required="!priceOnRequest"
            @keydown.enter.prevent="$el.reportValidity() && finishCameraDetail('negotiable')"
            class="public-input w-full">
        <input type="hidden" name="price" :value="priceOnRequest ? '' : raw">
        <div class="flex gap-2 overflow-x-auto">
            <button type="button" @click="if (raw) priceOnRequest = false; if ($refs.cameraPrice.reportValidity()) finishCameraDetail('negotiable')"
                class="shrink-0 rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white"
                x-text="cameraEditingDetail ? 'ذخیره تغییر' : 'ادامه'"></button>
            @if ($allowContactPrice)
                <button type="button" @click="priceOnRequest = true; finishCameraDetail('negotiable')"
                    class="shrink-0 rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold">قیمت را اعلام نمی‌کنم</button>
            @endif
        </div>
    </div>

    <div x-show="cameraDetailsStep === 'negotiable'" class="listing-camera-step space-y-3">
        <h2 class="text-sm font-bold">قیمت قابل مذاکره است؟</h2>
        <div class="flex gap-2 overflow-x-auto">
            <button type="button" @click="negotiable = true; finishCameraDetail('description')"
                class="shrink-0 rounded-xl border border-slate-200 px-5 py-2 text-sm font-bold">بله</button>
            <button type="button" @click="negotiable = false; finishCameraDetail('description')"
                class="shrink-0 rounded-xl border border-slate-200 px-5 py-2 text-sm font-bold">خیر</button>
        </div>
    </div>

    <div x-show="cameraDetailsStep === 'description'" class="listing-camera-step space-y-3">
        <label for="camera-listing-description" class="block text-sm font-bold">توضیحات (اختیاری)</label>
        <textarea id="camera-listing-description" name="description" rows="2" maxlength="5000"
            placeholder="وضعیت گوشی، لوازم همراه و شرایط فروش" class="public-input w-full">{{ old('description') }}</textarea>
        <button type="button" @click="finishCameraDetail('review')"
            class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white"
            x-text="cameraEditingDetail ? 'ذخیره تغییر' : 'پیش‌نمایش ارسال'">پیش‌نمایش ارسال</button>
    </div>

    <div x-show="cameraDetailsStep === 'review'" class="listing-camera-step space-y-3">
        <h2 class="text-sm font-bold">آمادهٔ ارسال برای بررسی</h2>
        <div class="flex gap-2 overflow-x-auto" aria-label="ویرایش اطلاعات آگهی">
            @foreach (['title' => 'عنوان', 'price' => 'قیمت', 'negotiable' => 'مذاکره', 'description' => 'توضیحات'] as $field => $label)
                <button type="button" @click="editCameraDetail(@js($field))" aria-label="ویرایش {{ $label }}"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-100 px-3 py-2 text-sm font-bold">
                    <x-heroicon-o-pencil class="h-3.5 w-3.5" /> {{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
            <p class="text-sm font-bold" role="status"
                x-text="cameraSubmitting ? 'در حال ارسال…' : 'ارسال برای بررسی ادمین'">ارسال برای بررسی ادمین</p>
            <button type="submit" aria-label="ارسال برای بررسی ادمین" :disabled="cameraSubmitting"
                class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-emerald-700 text-white shadow-md transition hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-emerald-700 disabled:opacity-60">
                <x-heroicon-s-paper-airplane class="h-6 w-6 rotate-180" aria-hidden="true" />
            </button>
        </div>
    </div>
</div>
