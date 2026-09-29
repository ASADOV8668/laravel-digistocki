import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.searchSuggest = (endpoint) => ({
    endpoint,
    query: '',
    suggestions: [],
    loading: false,
    open: false,

    async search() {
        const query = this.query.trim();

        if (query.length < 2) {
            this.suggestions = [];
            this.open = false;
            return;
        }

        this.loading = true;
        this.open = true;

        try {
            const response = await fetch(`${this.endpoint}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Search request failed');
            this.suggestions = await response.json();
        } catch {
            this.suggestions = [];
        } finally {
            this.loading = false;
        }
    },
});

window.pullToRefresh = () => ({
    startY: 0,
    distance: 0,
    refreshing: false,

    touchStart(event) {
        if (window.scrollY === 0) this.startY = event.touches[0].clientY;
    },

    touchMove(event) {
        if (!this.startY || this.refreshing) return;

        this.distance = Math.min(Math.max(event.touches[0].clientY - this.startY, 0), 96);
    },

    touchEnd() {
        if (this.distance >= 64) {
            this.refreshing = true;
            window.location.reload();
            return;
        }

        this.startY = 0;
        this.distance = 0;
    },
});

window.listingSearch = (suggestionsEndpoint, attributesEndpoint, initialModelId = null, initialAttributes = [], initialFilters = {}) => ({
    suggestionsEndpoint,
    attributesEndpoint,
    query: '',
    selectedBrandId: '',
    selectedModelId: initialModelId || '',
    suggestions: { brands: [], models: [] },
    attributes: initialAttributes || [],
    filters: initialFilters || {},
    loading: false,
    attributesLoading: false,
    open: false,
    filtersOpen: false,
    searchTimer: null,

    search() {
        clearTimeout(this.searchTimer);
        const query = this.query.trim();

        if (query.length < 2) {
            this.suggestions = { brands: [], models: [] };
            this.open = false;
            return;
        }

        this.searchTimer = setTimeout(() => this.fetchSuggestions(query), 250);
    },

    async fetchSuggestions(query) {
        this.loading = true;
        this.open = true;

        try {
            const response = await fetch(`${this.suggestionsEndpoint}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Search request failed');
            this.suggestions = await response.json();
        } catch {
            this.suggestions = { brands: [], models: [] };
        } finally {
            this.loading = false;
        }
    },

    selectBrand(brand) {
        this.selectedBrandId = brand.id;
        this.selectedModelId = '';
        this.query = brand.label;
        this.attributes = [];
        this.filters = {};
        this.open = false;
    },

    async selectModel(model) {
        const previousModelId = this.selectedModelId;
        this.selectedBrandId = model.brand_id;
        this.selectedModelId = model.id;
        this.query = model.label;
        if (previousModelId !== model.id) this.filters = {};
        this.open = false;
        await this.loadAttributes();
    },

    async loadAttributes() {
        if (!this.selectedModelId) {
            this.attributes = [];
            return;
        }

        this.attributesLoading = true;

        try {
            const response = await fetch(`${this.attributesEndpoint}/${this.selectedModelId}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) throw new Error('Attribute request failed');
            this.attributes = await response.json();
        } catch {
            this.attributes = [];
        } finally {
            this.attributesLoading = false;
        }
    },

    reset() {
        this.query = '';
        this.selectedBrandId = '';
        this.selectedModelId = '';
        this.attributes = [];
        this.filters = {};
        this.suggestions = { brands: [], models: [] };
        this.open = false;
    },
});

window.listingWizard = (attributesEndpoint, citiesEndpoint, initialModelId = null, initialAttributes = [], initialValues = {}, initialProvinceId = '', initialCityId = '') => ({
    attributesEndpoint,
    citiesEndpoint,
    step: 1,
    modelId: initialModelId || '',
    attributes: initialAttributes || [],
    values: initialValues || {},
    provinceId: initialProvinceId || '',
    cityId: initialCityId || '',
    cities: [],
    attributesLoading: false,
    citiesLoading: false,

    init() {
        if (this.modelId && !this.attributes.length) this.loadAttributes(false);
        if (this.provinceId) this.loadCities(false);
    },

    async loadAttributes(resetValues = true) {
        this.attributesLoading = true;
        this.attributes = [];
        if (resetValues) this.values = {};

        try {
            const response = await fetch(`${this.attributesEndpoint}/${this.modelId}?all=1`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Attribute request failed');
            this.attributes = await response.json();
        } catch {
            this.attributes = [];
        } finally {
            this.attributesLoading = false;
        }
    },

    async loadCities(resetCity = true) {
        if (resetCity) this.cityId = '';
        this.cities = [];
        if (!this.provinceId) return;
        this.citiesLoading = true;

        try {
            const response = await fetch(`${this.citiesEndpoint}/${this.provinceId}/cities`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('City request failed');
            this.cities = await response.json();
        } catch {
            this.cities = [];
        } finally {
            this.citiesLoading = false;
        }
    },

    next() {
        if (this.step === 1 && !this.modelId) return;
        if (this.step < 4) this.step += 1;
    },

    previous() {
        if (this.step > 1) this.step -= 1;
    },
});

Alpine.start();
