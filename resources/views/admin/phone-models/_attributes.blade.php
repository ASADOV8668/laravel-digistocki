@php($settingsById = $model?->modelAttributes->keyBy('attribute_id') ?? collect())
<fieldset class="space-y-2">
    <legend class="text-xs font-bold text-slate-700">ویژگی‌ها و گزینه‌های این مدل</legend>
    <div class="divide-y divide-slate-200 rounded-xl bg-slate-50 px-3">
        @foreach ($attributes as $attribute)
            @php($settings = $settingsById->get($attribute->id))
            <div x-data="{ attached: @js($settings !== null) }" class="space-y-2 py-2">
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-700">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="attribute_ids[]" value="{{ $attribute->id }}" x-model="attached"
                            @checked($settings)
                            class="rounded border-slate-300 text-accent focus:ring-accent">
                        {{ $attribute->name }}
                    </label>
                    <label x-show="attached" class="flex items-center gap-1">
                        <input type="checkbox" name="required_attribute_ids[]" value="{{ $attribute->id }}"
                            @checked($settings?->is_required)
                            class="rounded border-slate-300 text-accent focus:ring-accent">
                        الزامی
                    </label>
                </div>
                @if ($attribute->supportsCustomOptions())
                    <label x-show="attached" class="block text-xs text-slate-600">
                        گزینه‌های اختصاصی {{ $attribute->name }}
                        <input name="attribute_options[{{ $attribute->id }}]" type="text" maxlength="2000"
                            value="{{ implode('، ', $settings?->custom_options ?? []) }}"
                            placeholder="با کاما جدا کنید. خالی یعنی استفاده از گزینه‌های کاتالوگ."
                            class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-accent focus:ring-accent">
                    </label>
                @endif
            </div>
        @endforeach
    </div>
</fieldset>
