import './bootstrap';
import 'flowbite';
import { initTooltips } from 'flowbite';

import Alpine from 'alpinejs';
import './listing-attribute-repeater';

window.Alpine = Alpine;
window.refreshFlowbiteTooltips = () => initTooltips();

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
    citiesController: null,

    async loadCities() {
        this.citiesController?.abort();
        this.citiesController = null;
        this.cityId = '';
        this.cities = [];
        if (!this.provinceId) {
            this.citiesLoading = false;
            return;
        }

        const controller = new AbortController();
        this.citiesController = controller;
        this.citiesLoading = true;
        try {
            const response = await fetch(`${this.citiesEndpoint}/${this.provinceId}/cities`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('City request failed');
            this.cities = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.cities = [];
        } finally {
            if (this.citiesController === controller) {
                this.citiesController = null;
                this.citiesLoading = false;
            }
        }
    },

    search() {
        clearTimeout(this.searchTimer);
        const query = this.query.trim();
        this.selectedBrandId = '';
        this.selectedModelId = '';

        if (query.length < 2) {
            this.requestController?.abort();
            this.requestController = null;
            this.loading = false;
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
    citiesController: null,

    init() {
        if (this.provinceId) this.loadCities(false);
    },

    async loadCities(resetCity = true) {
        this.citiesController?.abort();
        this.citiesController = null;
        if (resetCity) this.cityId = '';
        this.cities = [];

        if (!this.provinceId) {
            this.citiesLoading = false;
            return;
        }

        const controller = new AbortController();
        this.citiesController = controller;
        this.citiesLoading = true;
        try {
            const response = await fetch(`${this.citiesEndpoint}/${this.provinceId}/cities`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('City request failed');
            this.cities = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.cities = [];
        } finally {
            if (this.citiesController === controller) {
                this.citiesController = null;
                this.citiesLoading = false;
            }
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

window.listingSearch = (suggestionsEndpoint, modelsEndpoint, attributesEndpoint, citiesEndpoint, initialModelId = null, initialAttributes = [], initialFilters = {}, initialProvinceId = '', initialCityId = '', initialQuery = '', initialBrandId = '', initialMinPrice = '', initialMaxPrice = '', initialSort = 'newest', initialPriceCeiling = 1000000000) => ({
    suggestionsEndpoint,
    modelsEndpoint,
    attributesEndpoint,
    citiesEndpoint,
    query: initialQuery || '',
    selectedBrandId: initialBrandId || '',
    selectedModelId: initialModelId || '',
    provinceId: initialProvinceId || '',
    cityId: initialCityId || '',
    priceCeiling: Number(initialPriceCeiling) || 1000000000,
    priceStep: 100000,
    minPrice: Number(initialMinPrice) || 0,
    maxPrice: Number(initialMaxPrice) || Number(initialPriceCeiling) || 1000000000,
    sort: initialSort || 'newest',
    cities: [],
    citiesLoading: false,
    suggestions: { brands: [], models: [] },
    models: [],
    modelsLoading: false,
    attributes: initialAttributes || [],
    filters: initialFilters || {},
    loading: false,
    attributesLoading: false,
    resultsLoading: false,
    open: false,
    filtersOpen: false,
    searchTimer: null,
    requestController: null,
    modelsController: null,
    resultsController: null,
    citiesController: null,
    attributesController: null,

    init() {
        this.maxPrice = Math.min(this.maxPrice || this.priceCeiling, this.priceCeiling);
        this.popstateHandler = () => this.fetchResults(window.location.href, 'none', true);
        window.addEventListener('popstate', this.popstateHandler);
        this.loadModels(this.selectedBrandId);
        if (this.provinceId) this.loadCities(false);
    },

    async loadModels(brandId = '') {
        this.modelsController?.abort();
        const controller = new AbortController();
        this.modelsController = controller;
        this.modelsLoading = true;
        const params = new URLSearchParams();
        if (brandId) params.set('brand_id', brandId);

        try {
            const response = await fetch(`${this.modelsEndpoint}?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Model request failed');
            this.models = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.models = [];
        } finally {
            if (this.modelsController === controller) {
                this.modelsController = null;
                this.modelsLoading = false;
            }
        }
    },

    async loadCities(resetCity = true) {
        if (resetCity) this.cityId = '';
        this.citiesController?.abort();
        this.citiesController = null;
        this.cities = [];
        this.citiesLoading = false;
        if (!this.provinceId) return;

        const controller = new AbortController();
        this.citiesController = controller;
        this.citiesLoading = true;
        try {
            const response = await fetch(this.citiesEndpoint + '/' + this.provinceId + '/cities', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('City request failed');
            this.cities = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.cities = [];
        } finally {
            if (this.citiesController === controller) {
                this.citiesController = null;
                this.citiesLoading = false;
            }
        }
    },

    async applyFilters(event) {
        const form = event.target;
        const url = new URL(form.action, window.location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        this.filtersOpen = false;
        await this.fetchResults(url.toString(), 'push');
    },

    syncPriceRange(changed) {
        this.minPrice = Math.max(0, Math.min(Number(this.minPrice) || 0, this.priceCeiling));
        this.maxPrice = Math.max(0, Math.min(Number(this.maxPrice) || this.priceCeiling, this.priceCeiling));
        if (this.minPrice > this.maxPrice) {
            if (changed === 'min') this.maxPrice = this.minPrice;
            else this.minPrice = this.maxPrice;
        }
    },

    formatPrice(value) {
        return new Intl.NumberFormat('fa-IR').format(Number(value) || 0);
    },

    colorHex(value) {
        return window.listingColorHex?.[value] || '#94a3b8';
    },

    activeFilterCount() {
        const dynamic = Object.values(this.filters || {}).filter((value) => Array.isArray(value)
            ? value.some((item) => item !== null && item !== '')
            : value !== null && value !== '').length;

        return [this.query, this.selectedBrandId, this.selectedModelId, this.provinceId, this.cityId]
            .filter(Boolean).length + (this.minPrice > 0 ? 1 : 0) + (this.maxPrice < this.priceCeiling ? 1 : 0) + dynamic;
    },

    async paginateResults(event) {
        const link = event.target.closest('.listing-pagination a, .listing-filter-chip');
        if (!link) return;

        event.preventDefault();
        const syncState = link.classList.contains('listing-filter-chip');
        await this.fetchResults(link.href, syncState ? 'push' : 'replace', syncState);
    },

    async fetchResults(url, historyMode = 'replace', syncState = false) {
        this.resultsController?.abort();
        const controller = new AbortController();
        this.resultsController = controller;
        this.resultsLoading = true;

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Results request failed');
            const data = await response.json();
            const container = document.getElementById('listing-results');
            if (container && data.html) {
                container.innerHTML = data.html;
                window.refreshFlowbiteTooltips?.();
            }
            if (historyMode === 'push') window.history.pushState({}, '', url);
            if (historyMode === 'replace') window.history.replaceState({}, '', url);
            if (syncState) await this.syncFormFromUrl(new URL(url, window.location.href));
            if (historyMode === 'none') {
                document.getElementById('listing-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        } finally {
            if (this.resultsController === controller) {
                this.resultsController = null;
                this.resultsLoading = false;
            }
        }
    },

    async syncFormFromUrl(url) {
        this.query = url.searchParams.get('q') || '';
        this.selectedBrandId = url.searchParams.get('brand_id') || '';
        this.selectedModelId = url.searchParams.get('phone_model_id') || '';
        this.provinceId = url.searchParams.get('province_id') || '';
        this.cityId = url.searchParams.get('city_id') || '';
        this.minPrice = Number(url.searchParams.get('min_price')) || 0;
        this.maxPrice = Number(url.searchParams.get('max_price')) || this.priceCeiling;
        this.sort = url.searchParams.get('sort') || 'newest';
        this.filters = {};
        url.searchParams.forEach((value, key) => {
            const match = key.match(/^filters\[(\d+)\](\[\])?$/);
            if (!match || value === '') return;
            const attributeId = match[1];
            this.filters[attributeId] = match[2]
                ? [...(Array.isArray(this.filters[attributeId]) ? this.filters[attributeId] : []), value]
                : value;
        });
        this.open = false;
        this.suggestions = { brands: [], models: [] };
        await this.loadModels(this.selectedBrandId);
        if (this.selectedModelId) await this.loadAttributes();
        else {
            this.cancelAttributeRequest();
            this.attributes = [];
        }
        if (this.provinceId) await this.loadCities(false);
        else {
            this.cityId = '';
            this.cities = [];
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

    async selectBrand(brand) {
        this.selectedBrandId = brand.id;
        this.selectedModelId = '';
        this.query = brand.label;
        this.cancelAttributeRequest();
        this.attributes = [];
        this.filters = {};
        this.cancelSuggestionRequest();
        this.open = false;
        await this.loadModels(brand.id);
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
        const model = this.models.find((item) => String(item.id) === String(modelId));
        this.selectedModelId = modelId;
        this.selectedBrandId = model?.brand_id || '';
        this.filters = {};
        await this.loadAttributes();
    },

    async loadAttributes() {
        this.cancelAttributeRequest();

        if (!this.selectedModelId) {
            this.attributes = [];
            return;
        }

        const controller = new AbortController();
        this.attributesController = controller;
        this.attributesLoading = true;

        try {
            const response = await fetch(`${this.attributesEndpoint}/${this.selectedModelId}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (!response.ok) throw new Error('Attribute request failed');
            this.attributes = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.attributes = [];
        } finally {
            if (this.attributesController === controller) {
                this.attributesController = null;
                this.attributesLoading = false;
            }
        }
    },

    cancelAttributeRequest() {
        this.attributesController?.abort();
        this.attributesController = null;
        this.attributesLoading = false;
    },

    reset() {
        this.query = '';
        this.selectedBrandId = '';
        this.selectedModelId = '';
        this.modelsController?.abort();
        this.modelsController = null;
        this.modelsLoading = false;
        this.models = [];
        this.provinceId = '';
        this.cityId = '';
        this.minPrice = 0;
        this.maxPrice = this.priceCeiling;
        this.sort = 'newest';
        this.citiesController?.abort();
        this.citiesController = null;
        this.resultsController?.abort();
        this.resultsController = null;
        this.cancelAttributeRequest();
        this.resultsLoading = false;
        this.citiesLoading = false;
        this.cities = [];
        this.attributes = [];
        this.filters = {};
        this.suggestions = { brands: [], models: [] };
        this.cancelSuggestionRequest();
        this.open = false;
        this.loadModels();
    },
});

window.listingWizard = () => ({
    modelsEndpoint: '',
    attributesEndpoint: '',
    citiesEndpoint: '',
    step: 1,
    brandId: '',
    modelId: '',
    models: [],
    attributes: [],
    values: {},
    provinceId: '',
    cityId: '',
    cities: [],
    attributesLoading: false,
    citiesLoading: false,
    modelsLoading: false,
    modelsController: null,
    attributesController: null,
    citiesController: null,

    init() {
        const root = this.$root;
        this.modelsEndpoint = root.dataset.modelsEndpoint || '';
        this.attributesEndpoint = this.modelsEndpoint;
        this.citiesEndpoint = root.dataset.citiesEndpoint || '';
        this.brandId = root.dataset.initialBrandId || '';
        this.modelId = root.dataset.initialModelId || '';
        this.provinceId = root.dataset.initialProvinceId || '';
        this.cityId = root.dataset.initialCityId || '';
        const attributesNode = root.querySelector('[data-listing-initial-attributes]');
        const valuesNode = root.querySelector('[data-listing-initial-values]');
        try { this.attributes = attributesNode ? JSON.parse(attributesNode.textContent || '[]') : []; } catch { this.attributes = []; }
        try { this.values = valuesNode ? JSON.parse(valuesNode.textContent || '{}') : {}; } catch { this.values = {}; }
        this.$watch('brandId', (value, previous) => { if (value && value !== previous) this.loadModels(); });
        if (this.brandId) this.loadModels(false);
        if (this.modelId && !this.attributes.length) this.loadAttributes(false);
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
        if (!this.brandId) {
            this.modelsLoading = false;
            return;
        }

        const controller = new AbortController();
        this.modelsController = controller;
        this.modelsLoading = true;
        try {
            const response = await fetch(`${this.modelsEndpoint}?brand_id=${encodeURIComponent(this.brandId)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Model request failed');
            this.models = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.models = [];
        } finally {
            if (this.modelsController === controller) {
                this.modelsController = null;
                this.modelsLoading = false;
            }
        }
    },

    async loadAttributes(resetValues = true) {
        this.attributesController?.abort();
        this.attributesController = null;
        this.attributesLoading = true;
        this.attributes = [];
        if (resetValues) this.values = {};
        window.dispatchEvent(new CustomEvent('listing-attributes-loaded', { detail: { attributes: [], values: this.values } }));

        if (!this.modelId) {
            this.attributesLoading = false;
            return;
        }

        const controller = new AbortController();
        this.attributesController = controller;

        try {
            const response = await fetch(`${this.attributesEndpoint}/${this.modelId}?all=1`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Attribute request failed');
            this.attributes = await response.json();
            window.dispatchEvent(new CustomEvent('listing-attributes-loaded', { detail: { attributes: this.attributes, values: this.values } }));
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.attributes = [];
                window.dispatchEvent(new CustomEvent('listing-attributes-loaded', { detail: { attributes: [], values: this.values } }));
            }
        } finally {
            if (this.attributesController === controller) {
                this.attributesController = null;
                this.attributesLoading = false;
            }
        }
    },

    async loadCities(resetCity = true) {
        this.citiesController?.abort();
        this.citiesController = null;
        if (resetCity) this.cityId = '';
        this.cities = [];
        if (!this.provinceId) {
            this.citiesLoading = false;
            return;
        }

        const controller = new AbortController();
        this.citiesController = controller;
        this.citiesLoading = true;

        try {
            const response = await fetch(`${this.citiesEndpoint}/${this.provinceId}/cities`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('City request failed');
            this.cities = await response.json();
        } catch (error) {
            if (error.name !== 'AbortError') this.cities = [];
        } finally {
            if (this.citiesController === controller) {
                this.citiesController = null;
                this.citiesLoading = false;
            }
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

window.favoriteToggle = (endpoint, initialFavorited = false, authenticated = false) => ({
    endpoint,
    favorited: initialFavorited,
    authenticated,
    loading: false,

    toggle() {
        if (!this.authenticated) {
            window.dispatchEvent(new CustomEvent('show-notification', { detail: { title: 'ورود لازم است', message: 'برای افزودن آگهی به علاقه‌مندی‌ها ابتدا وارد حساب کاربری شوید.', tone: 'info' } }));

            return;
        }

        if (this.loading) return;
        this.loading = true;
        const previous = this.favorited;
        this.favorited = !previous;

        fetch(this.endpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({}),
        }).then((response) => {
            if (!response.ok) throw new Error('Favorite request failed');
            return response.json();
        }).then((data) => {
            this.favorited = Boolean(data.favorited);
            window.dispatchEvent(new CustomEvent('show-notification', { detail: { title: this.favorited ? 'به علاقه‌مندی‌ها اضافه شد' : 'از علاقه‌مندی‌ها حذف شد', message: data.message || '', tone: this.favorited ? 'success' : 'info' } }));
        }).catch(() => {
            this.favorited = previous;
            window.dispatchEvent(new CustomEvent('show-notification', { detail: { title: 'خطا در ذخیره‌سازی', message: 'تغییر علاقه‌مندی انجام نشد؛ دوباره تلاش کنید.', tone: 'error' } }));
        }).finally(() => {
            this.loading = false;
        });
    },
});

window.toastNotifications = () => ({
    visible: false,
    title: '',
    message: '',
    tone: 'info',
    timer: null,

    show(detail = {}) {
        this.title = detail.title || 'اعلان';
        this.message = detail.message || '';
        this.tone = detail.tone || 'info';
        this.visible = true;
        clearTimeout(this.timer);
        this.timer = window.setTimeout(() => { this.visible = false; }, 4500);
    },
});

Alpine.start();
