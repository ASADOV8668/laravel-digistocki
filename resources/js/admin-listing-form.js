window.listingAdminForm = () => ({
    modelsEndpoint: '',
    attributesEndpoint: '',
    citiesEndpoint: '',
    usersEndpoint: '',
    brandId: '',
    modelId: '',
    models: [],
    attributes: [],
    values: {},
    modelsLoading: false,
    attributesLoading: false,
    provinceId: '',
    cityId: '',
    cities: [],
    citiesLoading: false,
    userId: '',
    userQuery: '',
    selectedUserLabel: '',
    userResults: [],
    modelsController: null,
    attributesController: null,
    citiesController: null,
    usersController: null,

    init() {
        const root = this.$root;
        this.modelsEndpoint = root.dataset.modelsEndpoint || '';
        this.attributesEndpoint = this.modelsEndpoint;
        this.citiesEndpoint = root.dataset.citiesEndpoint || '';
        this.usersEndpoint = root.dataset.usersEndpoint || '';
        this.brandId = root.dataset.initialBrandId || '';
        this.modelId = root.dataset.initialModelId || '';
        this.provinceId = root.dataset.initialProvinceId || '';
        this.cityId = root.dataset.initialCityId || '';
        this.userId = root.dataset.initialUserId || '';
        this.userQuery = root.dataset.initialUserQuery || '';
        this.selectedUserLabel = root.dataset.initialUserLabel || '';
        const attributesNode = root.querySelector('[data-admin-initial-attributes]');
        const valuesNode = root.querySelector('[data-admin-initial-values]');
        try { this.attributes = attributesNode ? JSON.parse(attributesNode.textContent || '[]') : []; } catch { this.attributes = []; }
        try { this.values = valuesNode ? JSON.parse(valuesNode.textContent || '{}') : {}; } catch { this.values = {}; }
        this.$watch('brandId', (value, previous) => { if (value && value !== previous) this.loadModels(); });
        if (this.brandId) this.loadModels(false);
        if (this.provinceId) this.loadCities(false);
    },

    async loadModels(resetModel = true) {
        this.modelsController?.abort();
        this.modelsController = null;
        if (resetModel) {
            this.modelId = '';
            this.attributes = [];
            this.values = {};
            window.dispatchEvent(new CustomEvent('listing-attributes-loaded', { detail: { attributes: [], values: {} } }));
        }
        this.models = [];
        if (!this.brandId) return;
        const controller = new AbortController();
        this.modelsController = controller;
        this.modelsLoading = true;
        try {
            const response = await fetch(`${this.modelsEndpoint}?brand_id=${encodeURIComponent(this.brandId)}`, { signal: controller.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Model request failed');
            this.models = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.models = [];
        } finally {
            if (this.modelsController === controller) { this.modelsController = null; this.modelsLoading = false; }
        }
    },

    async loadAttributes() {
        this.attributesController?.abort();
        this.attributesController = null;
        if (!this.modelId) { this.attributes = []; this.attributesLoading = false; return; }
        const controller = new AbortController();
        this.attributesController = controller;
        this.attributesLoading = true;
        try {
            const response = await fetch(`${this.attributesEndpoint}/${this.modelId}/attributes?all=1`, { signal: controller.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Attribute request failed');
            this.attributes = await response.json();
            window.dispatchEvent(new CustomEvent('listing-attributes-loaded', { detail: { attributes: this.attributes, values: this.values } }));
        } catch (error) {
            if (error.name !== 'AbortError') { this.attributes = []; window.dispatchEvent(new CustomEvent('listing-attributes-loaded', { detail: { attributes: [], values: this.values } })); }
        } finally {
            if (this.attributesController === controller) { this.attributesController = null; this.attributesLoading = false; }
        }
    },

    async loadCities(resetCity = true) {
        this.citiesController?.abort();
        this.citiesController = null;
        if (resetCity) this.cityId = '';
        this.cities = [];
        if (!this.provinceId) { this.citiesLoading = false; return; }
        const controller = new AbortController();
        this.citiesController = controller;
        this.citiesLoading = true;
        try {
            const response = await fetch(`${this.citiesEndpoint}/${this.provinceId}/cities`, { signal: controller.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('City request failed');
            this.cities = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.cities = [];
        } finally {
            if (this.citiesController === controller) { this.citiesController = null; this.citiesLoading = false; }
        }
    },

    async searchUsers() {
        this.usersController?.abort();
        this.usersController = null;
        if (this.userQuery.trim().length < 2) { this.userResults = []; return; }
        const controller = new AbortController();
        this.usersController = controller;
        try {
            const response = await fetch(`${this.usersEndpoint}?q=${encodeURIComponent(this.userQuery)}`, { signal: controller.signal, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('User request failed');
            this.userResults = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.userResults = [];
        } finally {
            if (this.usersController === controller) this.usersController = null;
        }
    },

    selectUser(user) {
        this.userId = user.id;
        this.userQuery = user.label;
        this.selectedUserLabel = `${user.label} · ${user.mobile || user.email || ''}`;
        this.userResults = [];
    },
});
