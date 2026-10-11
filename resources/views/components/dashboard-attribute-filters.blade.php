@props(['filterDefinitions' => collect()])

@if ($filterDefinitions->isNotEmpty())
    <div class="rounded-lg border border-slate-100 bg-slate-50 p-3 sm:col-span-2 lg:col-span-4">
        <p class="mb-3 text-xs font-black text-neutral">فیلتر ویژگی‌های مدل</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($filterDefinitions as $attribute)
                @php
                    $filterValue = request()->input('filters.'.$attribute->id);
                    $filterValues = array_values(array_filter((array) $filterValue, fn ($value) => $value !== null && $value !== ''));
                    $attributeType = $attribute->type->value;
                @endphp
                <div>
                    <label for="dashboard-filter-{{ $attribute->id }}" class="mb-1.5 block text-[11px] font-bold text-slate-600">{{ $attribute->name }}{{ $attribute->unit ? ' ('.$attribute->unit.')' : '' }}</label>
                    @if ($attribute->slug === 'color' && $attributeType === 'select')
                        <div class="flex min-h-10 flex-wrap items-center gap-1 rounded-lg border border-slate-200 bg-white p-1.5">
                            @foreach (($attribute->options ?? []) as $option)
                                <label class="inline-flex cursor-pointer items-center gap-1 rounded-md px-1.5 py-1 text-[10px] font-bold transition hover:bg-primary-50">
                                    <input type="radio" name="filters[{{ $attribute->id }}]" value="{{ $option }}" @checked(in_array($option, $filterValues, true)) class="sr-only peer">
                                    <span class="h-4 w-4 rounded-full border border-slate-300 peer-checked:ring-2 peer-checked:ring-primary/30" style="background-color: {{ \App\Support\ColorPalette::hex($option) }}"></span>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif ($attributeType === 'boolean')
                        <label class="flex min-h-10 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-neutral">
                            <input id="dashboard-filter-{{ $attribute->id }}" type="checkbox" name="filters[{{ $attribute->id }}]" value="1" @checked(in_array('1', $filterValues, true)) class="public-check">
                            دارد
                        </label>
                    @elseif ($attributeType === 'multi_select')
                        <select id="dashboard-filter-{{ $attribute->id }}" name="filters[{{ $attribute->id }}][]" multiple class="public-select min-h-20 text-xs">
                            @foreach (($attribute->options ?? []) as $option)
                                <option value="{{ $option }}" @selected(in_array($option, $filterValues, true))>{{ $option }}</option>
                            @endforeach
                        </select>
                    @elseif ($attributeType === 'select')
                        <select id="dashboard-filter-{{ $attribute->id }}" name="filters[{{ $attribute->id }}]" class="public-select text-xs">
                            <option value="">همه</option>
                            @foreach (($attribute->options ?? []) as $option)
                                <option value="{{ $option }}" @selected(in_array($option, $filterValues, true))>{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input id="dashboard-filter-{{ $attribute->id }}" name="filters[{{ $attribute->id }}]" value="{{ $filterValues[0] ?? '' }}" type="{{ in_array($attributeType, ['integer', 'decimal'], true) ? 'number' : 'text' }}" @if ($attributeType === 'decimal') step="0.01" @elseif ($attributeType === 'integer') step="1" @endif class="w-full rounded-lg border-slate-200 bg-white text-xs focus:border-primary focus:ring-primary/30">
                    @endif
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-[10px] text-slate-400">ویژگی‌ها بعد از انتخاب مدل، فقط برای همان مدل جستجو می‌شوند.</p>
    </div>
@endif
