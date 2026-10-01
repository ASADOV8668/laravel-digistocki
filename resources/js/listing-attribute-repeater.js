window.listingAttributeRepeater = () => ({
    definitions: [],
    rows: [],

    init() {
        const definitionsNode = this.$root.querySelector('[data-repeater-definitions]');
        const valuesNode = this.$root.querySelector('[data-repeater-values]');
        let definitions = [];
        let values = {};
        try { definitions = definitionsNode ? JSON.parse(definitionsNode.textContent || '[]') : []; } catch { definitions = []; }
        try { values = valuesNode ? JSON.parse(valuesNode.textContent || '{}') : {}; } catch { values = {}; }
        this.replaceAttributes(definitions, values, false);
    },

    replaceAttributes(attributes = [], values = {}, resetRows = true) {
        this.definitions = attributes || [];
        if (!resetRows && this.rows.length) return;

        const entries = Object.entries(values || {}).filter(([, value]) => {
            return value !== null && value !== '' && !(Array.isArray(value) && value.length === 0);
        });

        this.rows = entries
            .map(([attributeId, value]) => ({ attributeId: String(attributeId), value: this.normalizeValue(value) }))
            .filter((row) => this.attribute(row.attributeId));

        if (!this.rows.length && this.definitions.length) this.addRow();
    },

    normalizeValue(value) {
        return Array.isArray(value) ? [...value] : value;
    },

    attribute(attributeId) {
        return this.definitions.find((item) => String(item.id) === String(attributeId));
    },

    options(attributeId) {
        return this.attribute(attributeId)?.options || [];
    },

    addRow() {
        if (this.rows.length >= this.definitions.length) return;
        this.rows.push({ attributeId: '', value: '' });
    },

    removeRow(index) {
        this.rows.splice(index, 1);
        if (!this.rows.length && this.definitions.length) this.addRow();
    },

    setAttribute(row, attributeId) {
        row.attributeId = String(attributeId || '');
        const attribute = this.attribute(row.attributeId);
        row.value = attribute?.type === 'multi_select' ? [] : attribute?.type === 'boolean' ? false : '';
    },

    isSelected(attributeId, currentRow) {
        return this.rows.some((row) => row !== currentRow && String(row.attributeId) === String(attributeId));
    },

    inputName(row) {
        return this.attribute(row.attributeId)?.type === 'multi_select'
            ? `attributes[${row.attributeId}][]`
            : `attributes[${row.attributeId}]`;
    },

    hasValue(row) {
        return row.attributeId && this.attribute(row.attributeId);
    },
});

window.attachListingModelPicker = () => {
    document.querySelectorAll('[data-listing-brand-picker]').forEach((brandSelect) => {
        if (brandSelect.dataset.modelPickerBound === '1') return;

        const modelSelect = document.querySelector(brandSelect.dataset.modelTarget);
        if (!modelSelect) return;
        brandSelect.dataset.modelPickerBound = '1';

        const populate = (models, selectedValue = '') => {
            modelSelect.innerHTML = '<option value="">انتخاب مدل</option>';
            models.forEach((model) => {
                const option = document.createElement('option');
                option.value = model.id;
                option.textContent = `${model.label || model.name_fa || model.name} — ${model.secondary || model.name_en || model.name}`;
                option.selected = String(model.id) === String(selectedValue);
                modelSelect.appendChild(option);
            });
            modelSelect.disabled = false;
        };

        const load = async () => {
            const root = brandSelect.closest('[x-data]');
            const state = window.Alpine?.$data(root);
            modelSelect.disabled = true;
            modelSelect.innerHTML = '<option value="">در حال بارگذاری مدل‌ها...</option>';
            if (!brandSelect.value) {
                if (state) state.models = [];
                modelSelect.innerHTML = '<option value="">انتخاب مدل</option>';
                return;
            }

            try {
                const response = await fetch(`${brandSelect.dataset.modelsEndpoint}?brand_id=${encodeURIComponent(brandSelect.value)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Model request failed');
                const models = await response.json();
                const selectedValue = state?.modelId || '';
                if (state) {
                    state.models = models;
                    state.modelsLoading = false;
                }
                populate(models, selectedValue);
            } catch {
                if (state) state.models = [];
                modelSelect.innerHTML = '<option value="">مدلی پیدا نشد</option>';
                modelSelect.disabled = true;
            }
        };

        brandSelect.addEventListener('change', load);
        if (brandSelect.value) load();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.attachListingModelPicker());
} else {
    window.attachListingModelPicker();
}
