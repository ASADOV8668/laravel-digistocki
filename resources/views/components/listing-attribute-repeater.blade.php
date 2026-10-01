@props([
    'attributeDefinitions' => [],
    'values' => [],
])

<div
    x-data="listingAttributeRepeater(@js($attributeDefinitions), @js($values))"
    @listing-attributes-loaded.window="replaceAttributes($event.detail.attributes, $event.detail.values || {})"
    class="space-y-3"
>
    <div x-show="!definitions.length" x-cloak class="rounded-lg bg-warning/10 p-3 text-xs leading-6 text-neutral">
        برای این مدل هنوز ویژگی‌ای تعریف نشده است.
    </div>

    <div x-show="definitions.length" class="space-y-3">
        <template x-for="(row, index) in rows" :key="`listing-attribute-${index}`">
            <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-neutral">ویژگی</label>
                        <select
                            x-model="row.attributeId"
                            @change="setAttribute(row, $event.target.value)"
                            class="public-select"
                        >
                            <option value="">انتخاب ویژگی</option>
                            <template x-for="attribute in definitions" :key="attribute.id">
                                <option
                                    :value="attribute.id"
                                    :disabled="isSelected(attribute.id, row)"
                                    x-text="attribute.unit ? `${attribute.name} (${attribute.unit})` : attribute.name"
                                ></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="hasValue(row)">
                        <label class="mb-2 block text-xs font-bold text-neutral">مقدار</label>
                        <template x-if="attribute(row.attributeId)?.type === 'select'">
                            <select :name="inputName(row)" x-model="row.value" class="public-select">
                                <option value="">انتخاب مقدار</option>
                                <template x-for="option in options(row.attributeId)" :key="option">
                                    <option :value="option" x-text="option"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="attribute(row.attributeId)?.type === 'multi_select'">
                            <select multiple :name="inputName(row)" x-model="row.value" class="public-select min-h-28">
                                <template x-for="option in options(row.attributeId)" :key="option">
                                    <option :value="option" x-text="option"></option>
                                </template>
                            </select>
                        </template>
                        <template x-if="attribute(row.attributeId)?.type === 'boolean'">
                            <label class="flex min-h-11 items-center gap-2 text-sm font-bold text-neutral">
                                <input type="checkbox" :name="inputName(row)" value="1" x-model="row.value" class="public-check">
                                دارد
                            </label>
                        </template>
                        <template x-if="['integer', 'decimal'].includes(attribute(row.attributeId)?.type)">
                            <input
                                :name="inputName(row)"
                                x-model="row.value"
                                type="number"
                                :step="attribute(row.attributeId)?.type === 'decimal' ? '0.01' : '1'"
                                class="public-input"
                            >
                        </template>
                        <template x-if="attribute(row.attributeId)?.type === 'string'">
                            <input :name="inputName(row)" x-model="row.value" type="text" class="public-input">
                        </template>
                    </div>

                    <button type="button" @click="removeRow(index)" class="inline-flex h-11 items-center justify-center gap-1 rounded-lg px-3 text-xs font-bold text-error transition hover:bg-error/10" aria-label="حذف ویژگی">
                        <x-heroicon-o-trash class="h-4 w-4" />
                        حذف
                    </button>
                </div>
            </div>
        </template>

        <button type="button" @click="addRow" :disabled="rows.length >= definitions.length" class="inline-flex items-center gap-2 rounded-lg bg-primary-50 px-4 py-2.5 text-xs font-bold text-primary transition hover:bg-primary-100 disabled:cursor-not-allowed disabled:opacity-50">
            <x-heroicon-o-plus class="h-4 w-4" />
            افزودن ویژگی دیگر
        </button>
        <p class="text-xs leading-6 text-slate-400">برای هر مورد، ابتدا ویژگی و سپس مقدار آن را انتخاب کنید. امکان افزودن چند ویژگی وجود دارد.</p>
    </div>

    {{-- Server-rendered snapshot keeps existing multi-select values available before Alpine mounts. --}}
    @foreach ($attributeDefinitions as $attribute)
        @if (($attribute['type'] ?? null) === 'multi_select')
            @php($selected = (array) ($values[$attribute['id']] ?? []))
            <div class="hidden" aria-hidden="true">
                <select name="attributes[{{ $attribute['id'] }}][]" disabled>
                    @foreach ($attribute['options'] ?? [] as $option)
                        <option value="{{ $option }}" @selected(in_array($option, $selected, true))>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    @endforeach
</div>
