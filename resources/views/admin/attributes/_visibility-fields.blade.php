<div x-data="attributeVisibilityPicker(@js($phoneModelsPayload), @js($selectedModelIds ?? []), @js($selectedRule ?? 'all'))"
    class="space-y-3 rounded-xl border border-slate-200 bg-white p-3">
    <div>
        <label class="mb-1 block text-[11px] font-bold text-slate-600">شرط نمایش</label>
        <select name="visibility_rule" x-model="rule" class="w-full rounded-lg border-slate-200 text-sm focus:border-accent focus:ring-accent">
            <option value="all" @selected(($selectedRule ?? 'all') === 'all')>تمامی مدل‌ها</option>
            <option value="include" @selected(($selectedRule ?? 'all') === 'include')>انتخاب مدل خاص</option>
            <option value="exclude" @selected(($selectedRule ?? 'all') === 'exclude')>همه مدل‌ها بجز این موارد</option>
        </select>
    </div>
    <div x-show="rule !== 'all'" x-cloak class="space-y-2">
        <label class="mb-1 block text-[11px] font-bold text-slate-600">انتخاب مدل‌ها</label>
        <input x-model="query" type="search" placeholder="نام برند یا مدل را جستجو کنید"
            class="w-full rounded-lg border-slate-200 text-sm focus:border-accent focus:ring-accent">
        <select x-model="selected" multiple size="6"
            class="w-full rounded-lg border-slate-200 text-sm focus:border-accent focus:ring-accent">
            <template x-for="model in filteredModels()" :key="model.id">
                <option :value="String(model.id)" x-text="model.label"></option>
            </template>
        </select>
        <template x-for="modelId in selected" :key="`selected-${modelId}`">
            <input type="hidden" name="visibility_model_ids[]" :value="modelId">
        </template>
        <p class="text-[10px] leading-5 text-slate-400">برای انتخاب چند مدل، کلید Ctrl یا Command را نگه دارید. با نام برند هم می‌توانید تمام مدل‌های آن برند را پیدا کنید.</p>
    </div>
</div>
