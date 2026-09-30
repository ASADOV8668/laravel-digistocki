import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.searchSuggest = (endpoint) => ({
    endpoint,
    query: '',
    suggestions: [],
    loading: false,
    open: false,
    requestController: null,

    cancelRequest() {
        this.requestController?.abort();
        this.requestController = null;
    },

    async search() {
        const query = this.query.trim();

        if (query.length < 2) {
            this.cancelRequest();
            this.suggestions = [];
            this.loading = false;
            this.open = false;
            return;
        }

        this.cancelRequest();
        const controller = new AbortController();
        this.requestController = controller;
        this.loading = true;
        this.open = true;

        try {
            const response = await fetch(`${this.endpoint}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (!response.ok) throw new Error('Search request failed');
            this.suggestions = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.suggestions = [];
        } finally {
            if (this.requestController === controller) {
                this.requestController = null;
                this.loading = false;
            }
        }
    },
});

window.homeSearch = (citiesEndpoint, suggestionsEndpoint) => ({
    citiesEndpoint,
    suggestionsEndpoint,
    provinceId: '',
    cityId: '',
    cities: [],
    citiesLoading: false,
    query: '',
    selectedBrandId: '',
    selectedModelId: '',
    suggestions: { brands: [], models: [] },
    loading: false,
    open: false,
    searchTimer: null,
    requestController: null,

    async loadCities() {
        this.cityId = '';
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

    search() {
        clearTimeout(this.searchTimer);
        const query = this.query.trim();
        this.selectedBrandId = '';
        this.selectedModelId = '';

        if (query.length < 2) {
            this.suggestions = { brands: [], models: [] };
            this.open = false;
            return;
        }

        this.searchTimer = setTimeout(() => this.fetchSuggestions(query), 250);
    },

    async fetchSuggestions(query) {
        this.requestController?.abort();
        const controller = new AbortController();
        this.requestController = controller;
        this.loading = true;
        this.open = true;

        try {
            const response = await fetch(`${this.suggestionsEndpoint}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Search request failed');
            this.suggestions = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.suggestions = { brands: [], models: [] };
        } finally {
            if (this.requestController === controller) {
                this.requestController = null;
                this.loading = false;
            }
        }
    },

    selectBrand(brand) {
        this.selectedBrandId = brand.id;
        this.selectedModelId = '';
        this.query = brand.label;
        this.open = false;
    },

    selectModel(model) {
        this.selectedBrandId = model.brand_id;
        this.selectedModelId = model.id;
        this.query = model.label;
        this.open = false;
    },
});

window.storefrontLocation = (citiesEndpoint, initialProvinceId = '', initialCityId = '') => ({
    citiesEndpoint,
    provinceId: initialProvinceId || '',
    cityId: initialCityId || '',
    cities: [],
    citiesLoading: false,

    init() {
        if (this.provinceId) this.loadCities(false);
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
});

window.shareLink = (url) => ({
    url,
    copied: false,
    async share() {
        if (navigator.share) {
            await navigator.share({ title: document.title, url: this.url });
            return;
        }

        await this.copy();
    },

    async copy() {
        try {
            await navigator.clipboard.writeText(this.url);
        } catch {
            const input = document.createElement('input');
            input.value = this.url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }

        this.copied = true;
        window.setTimeout(() => this.copied = false, 2000);
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

window.pwaInstallPrompt = () => ({
    deferredPrompt: null,
    canInstall: false,
    dismissed: false,
    installed: false,
    isIos: false,

    init() {
        this.dismissed = window.localStorage.getItem('digistocki-pwa-install-dismissed') === '1';
        this.installed = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
        this.isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent)
            || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            this.deferredPrompt = event;
            this.canInstall = !this.installed && !this.dismissed;
        });

        window.addEventListener('appinstalled', () => {
            this.installed = true;
            this.canInstall = false;
            this.deferredPrompt = null;
            window.localStorage.removeItem('digistocki-pwa-install-dismissed');
        });
    },

    async install() {
        if (!this.deferredPrompt) return;

        const promptEvent = this.deferredPrompt;
        this.deferredPrompt = null;
        promptEvent.prompt();
        const { outcome } = await promptEvent.userChoice;

        if (outcome === 'accepted') {
            this.installed = true;
            this.canInstall = false;
        }
    },

    dismiss() {
        this.dismissed = true;
        this.canInstall = false;
        window.localStorage.setItem('digistocki-pwa-install-dismissed', '1');
    },
});

window.networkStatus = () => ({
    online: window.navigator.onLine,

    init() {
        window.addEventListener('online', () => {
            this.online = true;
        });

        window.addEventListener('offline', () => {
            this.online = false;
        });
    },
});

window.imagePicker = (maxMb = 5, maxFiles = 8) => ({
    previews: [],
    invalid: false,
    message: '',

    select(event) {
        this.revokePreviews();
        const files = Array.from(event.target.files || []);
        const oversized = files.find((file) => file.size > maxMb * 1024 * 1024);
        this.invalid = files.length > maxFiles || Boolean(oversized);
        this.message = files.length > maxFiles
            ? `حداکثر ${maxFiles} تصویر قابل انتخاب است.`
            : oversized
                ? `حجم تصویر «${oversized.name}» بیشتر از ${maxMb} مگابایت است.`
                : '';
        this.previews = files.slice(0, maxFiles).map((file) => ({
            name: file.name,
            url: URL.createObjectURL(file),
        }));
    },

    revokePreviews() {
        this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
        this.previews = [];
    },
});

window.listingSearch = (suggestionsEndpoint, attributesEndpoint, citiesEndpoint, initialModelId = null, initialAttributes = [], initialFilters = {}, initialProvinceId = '', initialCityId = '', initialQuery = '', initialBrandId = '') => ({
    suggestionsEndpoint,
    attributesEndpoint,
    citiesEndpoint,
    query: initialQuery || '',
    selectedBrandId: initialBrandId || '',
    selectedModelId: initialModelId || '',
    provinceId: initialProvinceId || '',
    cityId: initialCityId || '',
    cities: [],
    citiesLoading: false,
    suggestions: { brands: [], models: [] },
    attributes: initialAttributes || [],
    filters: initialFilters || {},
    loading: false,
    attributesLoading: false,
    open: false,
    filtersOpen: false,
    searchTimer: null,
    requestController: null,

    init() {
        if (this.provinceId) this.loadCities(false);
    },

    async loadCities(resetCity = true) {
        if (resetCity) this.cityId = '';
        this.cities = [];
        if (!this.provinceId) return;

        this.citiesLoading = true;
        try {
            const response = await fetch(this.citiesEndpoint + '/' + this.provinceId + '/cities', {
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

    cancelSuggestionRequest() {
        this.requestController?.abort();
        this.requestController = null;
    },

    search() {
        clearTimeout(this.searchTimer);
        const query = this.query.trim();

        if (query.length < 2) {
            this.cancelSuggestionRequest();
            this.suggestions = { brands: [], models: [] };
            this.loading = false;
            this.open = false;
            return;
        }

        this.searchTimer = setTimeout(() => this.fetchSuggestions(query), 250);
    },

    async fetchSuggestions(query) {
        this.cancelSuggestionRequest();
        const controller = new AbortController();
        this.requestController = controller;
        this.loading = true;
        this.open = true;

        try {
            const response = await fetch(`${this.suggestionsEndpoint}?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (!response.ok) throw new Error('Search request failed');
            this.suggestions = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.suggestions = { brands: [], models: [] };
        } finally {
            if (this.requestController === controller) {
                this.requestController = null;
                this.loading = false;
            }
        }
    },

    selectBrand(brand) {
        this.selectedBrandId = brand.id;
        this.selectedModelId = '';
        this.query = brand.label;
        this.attributes = [];
        this.filters = {};
        this.cancelSuggestionRequest();
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

    async selectModelId(modelId) {
        this.selectedModelId = modelId;
        this.selectedBrandId = '';
        this.filters = {};
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
        this.provinceId = '';
        this.cityId = '';
        this.cities = [];
        this.attributes = [];
        this.filters = {};
        this.suggestions = { brands: [], models: [] };
        this.cancelSuggestionRequest();
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
