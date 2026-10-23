import './bootstrap';
import 'flowbite';
import { initTooltips } from 'flowbite';

import Alpine from 'alpinejs';
import './listing-attribute-repeater';
import './listing-title';
import './location-components';
import './persian-digits';
import './file-upload-validation';

window.Alpine = Alpine;
window.refreshFlowbiteTooltips = () => initTooltips();

const publicThemeCookie = 'public_theme';
const publicThemeMaxAge = 60 * 60 * 24 * 365;

const readPublicTheme = () => document.cookie.match(new RegExp(`(?:^|;\\s*)${publicThemeCookie}=([^;]+)`))?.[1] === 'dark' ? 'dark' : 'light';

const syncPublicThemeControls = () => {
    const theme = readPublicTheme();
    const isDark = theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.dataset.theme = theme;
    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-pressed', String(isDark));
        toggle.setAttribute('aria-label', isDark ? 'فعال‌کردن حالت روز' : 'فعال‌کردن حالت شب');
        toggle.querySelector('[data-theme-icon-light]')?.classList.toggle('hidden', isDark);
        toggle.querySelector('[data-theme-icon-dark]')?.classList.toggle('hidden', !isDark);
        const label = toggle.querySelector('[data-theme-label]');
        if (label) label.textContent = isDark ? 'حالت روز' : 'حالت شب';
    });
};

window.setPublicTheme = (theme) => {
    const nextTheme = theme === 'dark' ? 'dark' : 'light';
    document.cookie = `${publicThemeCookie}=${nextTheme}; Max-Age=${publicThemeMaxAge}; Path=/; SameSite=Lax`;
    syncPublicThemeControls();
};

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');
    if (!toggle) return;
    window.setPublicTheme(readPublicTheme() === 'dark' ? 'light' : 'dark');
});

document.addEventListener('DOMContentLoaded', syncPublicThemeControls);

document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-listing-view-link]');
    if (!link) return;

    const view = link.dataset.view;
    if (!['list', 'grid'].includes(view)) return;

    document.cookie = `listing_view=${view}; Max-Age=${60 * 60 * 24 * 365}; Path=/; SameSite=Lax`;
});

const initListingCarouselGestures = () => {
    document.querySelectorAll('[data-listing-carousel]').forEach((carousel) => {
        if (carousel.dataset.gestureReady === 'true') return;

        const currentSlideIndex = () => {
            const items = [...carousel.querySelectorAll('[data-carousel-item]')];
            const active = items.findIndex((item) => item.classList.contains('translate-x-0'));
            if (active >= 0) return active;
            return Math.max(0, items.findIndex((item) => item.dataset.carouselItem === 'active'));
        };

        const slideTo = (position) => {
            const indicator = carousel.querySelector(`[data-carousel-slide-to="${position}"]`);
            if (indicator) {
                indicator.click();
                return true;
            }

            const instance = window.FlowbiteInstances?.getInstance('Carousel', carousel.id);
            if (instance && Number.isInteger(position)) {
                instance.slideTo(position);
                return true;
            }

            return false;
        };

        const move = (direction) => {
            const total = carousel.querySelectorAll('[data-carousel-item]').length;
            if (total < 2) return false;
            const current = currentSlideIndex();
            const next = (current + direction + total) % total;
            return slideTo(next);
        };

        carousel.querySelectorAll('[data-gallery-thumbnail]').forEach((thumbnail) => {
            thumbnail.addEventListener('click', () => {
                const position = Number(thumbnail.dataset.carouselSlideTo);
                carousel.querySelectorAll('[data-gallery-thumbnail]').forEach((item) => item.classList.remove('border-primary'));
                if (Number.isInteger(position)) thumbnail.classList.add('border-primary');
            });
        });

        let startX = 0;
        let startY = 0;
        let moved = false;

        carousel.addEventListener('touchstart', (event) => {
            const touch = event.changedTouches[0];
            startX = touch.clientX;
            startY = touch.clientY;
            moved = false;
        }, { passive: true });

        carousel.addEventListener('touchmove', (event) => {
            const touch = event.changedTouches[0];
            moved = Math.abs(touch.clientX - startX) > 10 || Math.abs(touch.clientY - startY) > 10;
        }, { passive: true });

        carousel.addEventListener('touchend', (event) => {
            const touch = event.changedTouches[0];
            const deltaX = touch.clientX - startX;
            const deltaY = touch.clientY - startY;
            if (Math.abs(deltaX) < 45 || Math.abs(deltaX) <= Math.abs(deltaY)) return;

            if (!move(deltaX > 0 ? -1 : 1)) return;
            carousel.dataset.ignoreClick = 'true';
            window.setTimeout(() => delete carousel.dataset.ignoreClick, 350);
        }, { passive: true });

        carousel.addEventListener('click', (event) => {
            if (carousel.dataset.ignoreClick !== 'true') return;
            event.preventDefault();
            event.stopPropagation();
            delete carousel.dataset.ignoreClick;
        }, true);

        carousel.dataset.gestureReady = 'true';
    });

    document.querySelectorAll('[data-carousel-open-index]').forEach((trigger) => {
        if (trigger.dataset.carouselSyncReady === 'true') return;
        trigger.addEventListener('click', () => {
            const position = Number(trigger.dataset.carouselOpenIndex);
            const fullscreen = document.querySelector('#listing-gallery-fullscreen-carousel');
            const indicator = fullscreen?.querySelector(`[data-carousel-slide-to="${position}"]`);
            if (indicator && Number.isInteger(position)) indicator.click();
        });
        trigger.dataset.carouselSyncReady = 'true';
    });
};

window.addEventListener('load', initListingCarouselGestures);

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

const initOtpAutofill = () => {
    document.querySelectorAll('[data-otp-autofill]').forEach((input) => {
        if (input.dataset.otpAutofillReady === 'true') return;
        input.dataset.otpAutofillReady = 'true';
        input.setAttribute('autocomplete', 'one-time-code');

        if (!('OTPCredential' in window) || !navigator.credentials?.get) return;
        const controller = new AbortController();
        input.closest('form')?.addEventListener('submit', () => controller.abort(), { once: true });
        navigator.credentials.get({ otp: { transport: ['sms'] }, signal: controller.signal })
            .then((credential) => {
                if (credential?.code) {
                    input.value = credential.code;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            })
            .catch(() => {});
    });
};

window.addEventListener('DOMContentLoaded', initOtpAutofill);

window.homeSearch = (citiesEndpoint, suggestionsEndpoint, attributesEndpoint = '/listings/models') => ({
    citiesEndpoint,
    suggestionsEndpoint,
    attributesEndpoint,
    provinceId: '',
    cityId: '',
    cityIds: [],
    cities: [],
    citiesLoading: false,
    locationLoading: false,
    locationLabel: 'همه ایران',
    locationModalOpen: false,
    locationError: '',
    query: '',
    searchQuery: '',
    selectedBrandId: '',
    selectedModelId: '',
    selectedModelLabel: '',
    suggestions: { brands: [], models: [] },
    attributes: [],
    filters: {},
    attributesLoading: false,
    attributesController: null,
    quickStep: 'storage',
    loading: false,
    open: false,
    searchTimer: null,
    requestController: null,
    citiesController: null,

    async loadCities() {
        this.citiesController?.abort();
        this.citiesController = null;
        this.cityId = '';
        this.cityIds = [];
        this.cities = [];
        this.locationLabel = this.provinceId ? 'همه شهرهای استان' : 'همه ایران';
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

    toggleCity(cityId) {
        const id = String(cityId);
        this.cityIds = this.cityIds.includes(id)
            ? this.cityIds.filter((item) => item !== id)
            : [...this.cityIds, id];
        this.cityId = this.cityIds[0] || '';
        const city = this.cities.find((item) => String(item.id) === id);
        if (this.cityIds.length === 1 && city) this.locationLabel = city.name;
        if (this.cityIds.length > 1) this.locationLabel = this.cityIds.length + ' شهر';
        if (!this.cityIds.length) this.locationLabel = this.provinceId ? 'همه شهرهای استان' : 'همه ایران';
    },

    openLocationPicker() {
        this.locationModalOpen = true;
        this.locationError = '';
        this.useLocation();
    },

    confirmLocation() {
        this.locationModalOpen = false;
        this.locationError = '';
    },

    clearLocation() {
        this.provinceId = '';
        this.cityId = '';
        this.cityIds = [];
        this.cities = [];
        this.locationLabel = 'همه ایران';
        this.locationError = '';
        this.locationModalOpen = false;
    },

    useLocation() {
        if (!navigator.geolocation) {
            window.dispatchEvent(new CustomEvent('show-notification', { detail: { tone: 'error', title: 'مکان‌یابی در دسترس نیست', message: 'مرورگر شما از مکان‌یابی پشتیبانی نمی‌کند.' } }));
            return;
        }

        if (!window.isSecureContext) {
            this.locationError = 'مکان‌یابی فقط در HTTPS یا localhost فعال می‌شود.';
            window.dispatchEvent(new CustomEvent('show-notification', { detail: { tone: 'error', title: 'اتصال امن لازم است', message: this.locationError } }));
            return;
        }

        this.locationLoading = true;
        this.locationError = '';
        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                const params = new URLSearchParams({ latitude: position.coords.latitude, longitude: position.coords.longitude });
                const response = await fetch(`/locations/reverse?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const location = await response.json();
                if (!response.ok || !location.city_id) throw new Error('Location not found');
                this.provinceId = String(location.province_id || '');
                await this.loadCities();
                this.cityIds = [String(location.city_id)];
                this.cityId = String(location.city_id);
                this.locationLabel = location.city_name || 'موقعیت شما';
                this.locationModalOpen = false;
                window.dispatchEvent(new CustomEvent('show-notification', { detail: { tone: 'success', title: 'موقعیت انتخاب شد', message: `آگهی‌های ${this.locationLabel} آماده جستجو هستند.` } }));
            } catch {
                this.locationError = 'موقعیت پیدا نشد؛ استان و شهر یا چند شهر را دستی انتخاب کنید.';
                window.dispatchEvent(new CustomEvent('show-notification', { detail: { tone: 'error', title: 'موقعیت پیدا نشد', message: 'استان و شهر را به‌صورت دستی انتخاب کنید.' } }));
            } finally {
                this.locationLoading = false;
            }
        }, (error) => {
            this.locationLoading = false;
            this.locationError = error?.code === 1
                ? 'اجازه مکان‌یابی داده نشد. اگر قبلاً آن را رد کرده‌اید، از قفل کنار آدرس سایت اجازه Location را روی Allow بگذارید و دوباره تلاش کنید.'
                : error?.code === 3
                    ? 'دریافت موقعیت بیش از حد طول کشید؛ دوباره تلاش کنید یا محدوده را دستی انتخاب کنید.'
                    : 'موقعیت شما دریافت نشد؛ دوباره تلاش کنید یا محدوده را دستی انتخاب کنید.';
            window.dispatchEvent(new CustomEvent('show-notification', { detail: { tone: 'error', title: 'مکان‌یابی انجام نشد', message: this.locationError } }));
        }, { enableHighAccuracy: false, timeout: 15000, maximumAge: 0 });
    },

    search() {
        clearTimeout(this.searchTimer);
        const query = this.query.trim();
        this.searchQuery = query;
        this.selectedBrandId = '';
        this.selectedModelId = '';
        this.selectedModelLabel = '';
        this.attributes = [];
        this.filters = {};
        this.quickStep = 'storage';

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

    async selectBrand(brand) {
        this.selectedBrandId = brand.id;
        this.selectedModelId = '';
        this.selectedModelLabel = '';
        this.attributes = [];
        this.filters = {};
        this.quickStep = 'storage';
        this.query = brand.label;
        this.searchQuery = brand.label;
        this.loading = true;
        this.open = true;
        try {
            const response = await fetch(`${this.attributesEndpoint}?brand_id=${encodeURIComponent(brand.id)}&scope=search&active_only=1`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error('Model request failed');
            this.suggestions = { brands: [], models: await response.json() };
        } catch {
            this.suggestions = { brands: [], models: [] };
        } finally {
            this.loading = false;
        }
    },

    async selectModel(model) {
        this.selectedBrandId = model.brand_id;
        this.selectedModelId = model.id;
        this.selectedModelLabel = model.label;
        this.query = model.label;
        this.searchQuery = model.label;
        this.filters = {};
        this.quickStep = 'storage';
        this.open = true;
        await this.loadAttributes();
    },

    async loadAttributes() {
        this.attributesController?.abort();

        if (!this.selectedModelId) {
            this.attributes = [];
            return;
        }

        const controller = new AbortController();
        this.attributesController = controller;
        this.attributesLoading = true;
        const params = new URLSearchParams({ active_only: '1' });
        Object.entries(this.filters || {}).forEach(([attributeId, value]) => {
            if (Array.isArray(value)) value.forEach((item) => item && params.append(`filters[${attributeId}][]`, item));
            else if (value !== null && value !== '') params.set(`filters[${attributeId}]`, value);
        });

        try {
            const response = await fetch(`${this.attributesEndpoint}/${this.selectedModelId}/attributes?${params.toString()}`, {
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

    attributeOptions(attribute) {
        return Array.isArray(attribute.values)
            ? attribute.values
            : (Array.isArray(attribute.options) ? attribute.options : []);
    },

    quickAttributes() {
        return this.attributes.filter((attribute) => ['storage', 'color'].includes(attribute.slug));
    },

    quickAttribute(slug) {
        return this.attributes.find((attribute) => attribute.slug === slug);
    },

    quickOptions(slug) {
        const attribute = this.quickAttribute(slug);
        return attribute ? this.attributeOptions(attribute) : [];
    },

    selectQuickAttribute(attribute, value) {
        this.filters[attribute.id] = value;
        if (attribute.slug === 'storage') {
            this.quickStep = 'color';
            this.query = `${this.selectedModelLabel} · ${value}${attribute.unit ? ` ${attribute.unit}` : ''}`;
            this.loadAttributes();
            this.open = true;
            return;
        }

        this.quickStep = 'done';
        this.query = `${this.selectedModelLabel}${this.filters[this.quickAttribute('storage')?.id] ? ` · ${this.filters[this.quickAttribute('storage').id]} GB` : ''} · ${value}`;
        this.open = false;
    },

    resetModelSelection() {
        this.selectedBrandId = '';
        this.selectedModelId = '';
        this.selectedModelLabel = '';
        this.query = '';
        this.searchQuery = '';
        this.attributes = [];
        this.filters = {};
        this.quickStep = 'storage';
        this.open = false;
    },

    isQuickAttributeVisible(attribute) {
        if (attribute.slug !== 'color') return true;
        const storage = this.attributes.find((item) => item.slug === 'storage');
        return !storage || Boolean(this.filters[storage.id]);
    },

    async attributeChanged() {
        await this.loadAttributes();
    },
});

window.storefrontDirectory = (citiesEndpoint, initialProvinceId = '', initialCityIds = []) => ({
    citiesEndpoint,
    provinceId: initialProvinceId || '',
    cityIds: (initialCityIds || []).map(String),
    cities: [],
    citiesLoading: false,
    citiesController: null,
    locationLoading: false,
    locationModalOpen: false,
    locationError: '',
    locationLabel: initialCityIds?.length ? initialCityIds.length + ' شهر' : (initialProvinceId ? 'همه شهرهای استان' : 'همه ایران'),

    init() {
        if (this.provinceId) this.loadCities(false);
    },

    async loadCities(resetCities = true) {
        this.citiesController?.abort();
        this.citiesController = null;
        if (resetCities) {
            this.cityIds = [];
            this.locationLabel = this.provinceId ? 'همه شهرهای استان' : 'همه ایران';
        }
        this.cities = [];
        if (!this.provinceId) return;

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

    toggleCity(cityId) {
        const id = String(cityId);
        this.cityIds = this.cityIds.includes(id)
            ? this.cityIds.filter((item) => item !== id)
            : [...this.cityIds, id];
        if (this.cityIds.length === 1) {
            this.locationLabel = this.cities.find((city) => String(city.id) === id)?.name || '۱ شهر';
        } else if (this.cityIds.length > 1) {
            this.locationLabel = this.cityIds.length + ' شهر';
        } else {
            this.locationLabel = this.provinceId ? 'همه شهرهای استان' : 'همه ایران';
        }
    },

    openLocationPicker() {
        this.locationModalOpen = true;
        this.locationError = '';
        this.useLocation();
    },

    confirmLocation() {
        this.locationModalOpen = false;
        this.locationError = '';
    },

    clearLocation() {
        this.provinceId = '';
        this.cityIds = [];
        this.cities = [];
        this.locationLabel = 'همه ایران';
        this.locationError = '';
        this.locationModalOpen = false;
    },

    useLocation() {
        if (!navigator.geolocation) {
            this.locationError = 'مرورگر شما از مکان‌یابی پشتیبانی نمی‌کند؛ محدوده را دستی انتخاب کنید.';
            return;
        }

        if (!window.isSecureContext) {
            this.locationError = 'مکان‌یابی فقط در HTTPS یا localhost فعال می‌شود.';
            return;
        }

        this.locationLoading = true;
        this.locationError = '';
        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                const params = new URLSearchParams({ latitude: position.coords.latitude, longitude: position.coords.longitude });
                const response = await fetch('/locations/reverse?' + params, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const location = await response.json();
                if (!response.ok || !location.city_id) throw new Error('Location not found');
                this.provinceId = String(location.province_id || '');
                this.cityIds = [String(location.city_id)];
                this.locationLabel = location.city_name || 'موقعیت شما';
                await this.loadCities(false);
                this.locationModalOpen = false;
            } catch {
                this.locationError = 'موقعیت پیدا نشد؛ استان و شهر یا چند شهر را دستی انتخاب کنید.';
            } finally {
                this.locationLoading = false;
            }
        }, (error) => {
            this.locationLoading = false;
            this.locationError = error?.code === 1
                ? 'اجازه مکان‌یابی داده نشد. اگر قبلاً آن را رد کرده‌اید، از قفل کنار آدرس سایت اجازه Location را روی Allow بگذارید و دوباره تلاش کنید.'
                : error?.code === 3
                    ? 'دریافت موقعیت بیش از حد طول کشید؛ دوباره تلاش کنید یا محدوده را دستی انتخاب کنید.'
                    : 'موقعیت شما دریافت نشد؛ دوباره تلاش کنید یا محدوده را دستی انتخاب کنید.';
        }, { enableHighAccuracy: false, timeout: 15000, maximumAge: 0 });
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

window.imagePicker = (maxMb = 5, maxFiles = 8, initialPrimaryImageId = '') => ({
    previews: [],
    fileInput: null,
    invalid: false,
    message: '',
    primaryIndex: '0',
    primaryImageId: initialPrimaryImageId ? String(initialPrimaryImageId) : '',

    setPrimary(index = '', imageId = '') {
        this.primaryIndex = imageId ? '' : String(index);
        this.primaryImageId = imageId ? String(imageId) : '';
    },

    syncFiles() {
        if (!this.fileInput || typeof DataTransfer === 'undefined') return;

        const transfer = new DataTransfer();
        this.previews.forEach((preview) => transfer.items.add(preview.file));
        this.fileInput.files = transfer.files;
    },

    removePreview(index) {
        const preview = this.previews[index];
        if (!preview) return;

        URL.revokeObjectURL(preview.url);
        this.previews.splice(index, 1);
        this.syncFiles();

        if (this.primaryImageId !== '') return;

        const currentPrimaryIndex = Number(this.primaryIndex);
        if (!this.previews.length) {
            this.primaryIndex = '';
        } else if (index === currentPrimaryIndex) {
            this.primaryIndex = String(Math.min(index, this.previews.length - 1));
        } else if (index < currentPrimaryIndex) {
            this.primaryIndex = String(currentPrimaryIndex - 1);
        }
    },

    select(event) {
        this.revokePreviews();
        this.fileInput = event.target;
        const files = Array.from(event.target.files || []);
        const oversized = files.filter((file) => file.size > maxMb * 1024 * 1024);
        const validFiles = files.filter((file) => file.size <= maxMb * 1024 * 1024).slice(0, maxFiles);
        this.invalid = files.length > maxFiles || oversized.length > 0;
        this.message = oversized.length > 0
            ? `حجم تصویر «${oversized[0].name}» بیشتر از ${maxMb} مگابایت است و از انتخاب‌ها حذف شد.`
            : files.length > maxFiles
                ? `حداکثر ${maxFiles} تصویر قابل انتخاب است؛ تصاویر اضافی حذف شدند.`
                : '';
        if (typeof DataTransfer !== 'undefined') {
            const transfer = new DataTransfer();
            validFiles.forEach((file) => transfer.items.add(file));
            event.target.files = transfer.files;
        } else if (this.invalid) {
            event.target.value = '';
        }
        if (this.primaryImageId === '' && validFiles.length > 0 && Number(this.primaryIndex) >= validFiles.length) {
            this.primaryIndex = '0';
        }
        this.previews = validFiles.map((file) => ({
            file,
            name: file.name,
            url: URL.createObjectURL(file),
        }));

        if (!validFiles.length && this.primaryImageId === '') this.primaryIndex = '';
    },

    revokePreviews() {
        this.previews.forEach((preview) => URL.revokeObjectURL(preview.url));
        this.previews = [];
    },
});

window.listingSearch = (suggestionsEndpoint, modelsEndpoint, attributesEndpoint, citiesEndpoint, initialModelId = null, initialAttributes = [], initialFilters = {}, initialProvinceId = '', initialCityId = '', initialQuery = '', initialBrandId = '', initialMinPrice = '', initialMaxPrice = '', initialSort = 'newest', initialPriceCeiling = 1000000000, initialCityIds = [], initialPriceFloor = 0, initialActiveOnly = true) => ({
    suggestionsEndpoint,
    modelsEndpoint,
    attributesEndpoint,
    citiesEndpoint,
    query: initialQuery || '',
    searchQuery: initialQuery || '',
    selectedBrandId: initialBrandId || '',
    selectedModelId: initialModelId || '',
    selectedModelLabel: '',
    provinceId: initialProvinceId || '',
    cityId: initialCityId || '',
    cityIds: (Array.isArray(initialCityIds) ? initialCityIds : (initialCityIds ? [initialCityIds] : [])).map(String).length
        ? (Array.isArray(initialCityIds) ? initialCityIds : [initialCityIds]).map(String)
        : (initialCityId ? [String(initialCityId)] : []),
    priceFloor: Number(initialPriceFloor) || 0,
    priceCeiling: Number(initialPriceCeiling) || 1000000000,
    priceStep: 100000,
    minPrice: Number(initialMinPrice) || Number(initialPriceFloor) || 0,
    maxPrice: Number(initialMaxPrice) || Number(initialPriceCeiling) || 1000000000,
    sort: initialSort || 'newest',
    activeOnly: initialActiveOnly !== false,
    cities: [],
    citiesLoading: false,
    suggestions: { brands: [], models: [] },
    models: [],
    modelsLoading: false,
    attributes: initialAttributes || [],
    filters: initialFilters || {},
    loading: false,
    attributesLoading: false,
    quickStep: 'storage',
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
        this.minPrice = Math.max(this.priceFloor, Math.min(this.minPrice || this.priceFloor, this.priceCeiling));
        this.maxPrice = Math.max(this.minPrice, Math.min(this.maxPrice || this.priceCeiling, this.priceCeiling));
        this.syncQuickStep();
        this.popstateHandler = () => this.fetchResults(window.location.href, 'none', true);
        window.addEventListener('popstate', this.popstateHandler);
        this.loadModels(this.selectedBrandId);
        if (this.provinceId) this.loadCities(false);
    },

    async loadModels(brandId = '') {
        this.modelsController?.abort();
        const controller = new AbortController();
        this.modelsController = controller;
        if (!brandId) {
            this.models = [];
            this.modelsLoading = false;
            this.modelsController = null;
            return;
        }
        this.modelsLoading = true;
        const params = new URLSearchParams();
        if (brandId) params.set('brand_id', brandId);
        params.set('scope', 'search');
        params.set('active_only', this.activeOnly ? '1' : '0');

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
        if (resetCity) {
            this.cityId = '';
            this.cityIds = [];
        }
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
        form.closest('aside')?.querySelector('[data-drawer-hide]')?.click();
        this.filtersOpen = false;
        await this.fetchResults(url.toString(), 'push');
    },

    syncPriceRange(changed) {
        this.minPrice = Math.max(this.priceFloor, Math.min(Number(this.minPrice) || this.priceFloor, this.priceCeiling));
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

    attributeOptions(attribute) {
        return Array.isArray(attribute.values)
            ? attribute.values
            : (Array.isArray(attribute.options) ? attribute.options : []);
    },

    quickAttributes() {
        return this.attributes.filter((attribute) => ['storage', 'color'].includes(attribute.slug));
    },

    isQuickAttributeVisible(attribute) {
        return true;
    },

    activeFilterCount() {
        const dynamic = Object.values(this.filters || {}).filter((value) => Array.isArray(value)
            ? value.some((item) => item !== null && item !== '')
            : value !== null && value !== '').length;

        return [this.query, this.selectedBrandId, this.selectedModelId, this.provinceId]
            .filter(Boolean).length + (this.cityIds.length ? 1 : 0) + (this.minPrice > this.priceFloor ? 1 : 0) + (this.maxPrice < this.priceCeiling ? 1 : 0) + (!this.activeOnly ? 1 : 0) + dynamic;
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
                window.initInfiniteListingFeeds?.(container);
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
        this.searchQuery = this.query;
        this.selectedBrandId = url.searchParams.get('brand_id') || '';
        this.selectedModelId = url.searchParams.get('phone_model_id') || '';
        this.provinceId = url.searchParams.get('province_id') || '';
        this.cityId = url.searchParams.get('city_id') || '';
        this.cityIds = [...new Set(url.searchParams.getAll('city_ids[]').concat(url.searchParams.getAll('city_ids')).filter(Boolean).map(String))];
        if (!this.cityIds.length && this.cityId) this.cityIds = [String(this.cityId)];
        this.minPrice = Number(url.searchParams.get('min_price')) || this.priceFloor;
        this.maxPrice = Number(url.searchParams.get('max_price')) || this.priceCeiling;
        this.sort = url.searchParams.get('sort') || 'newest';
        this.activeOnly = url.searchParams.get('active_only') !== '0';
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
        this.selectedModelLabel = this.models.find((model) => String(model.id) === String(this.selectedModelId))?.label || this.query;
        if (this.selectedModelId) await this.loadAttributes();
        else {
            this.cancelAttributeRequest();
            this.attributes = [];
        }
        if (this.provinceId) await this.loadCities(false);
        else {
            this.cityId = '';
            this.cityIds = [];
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
        this.searchQuery = query;

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
            const params = new URLSearchParams({
                q: query,
                active_only: this.activeOnly ? '1' : '0',
            });
            const response = await fetch(`${this.suggestionsEndpoint}?${params.toString()}`, {
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
        this.selectedModelLabel = '';
        this.query = brand.label;
        this.searchQuery = brand.label;
        this.cancelAttributeRequest();
        this.attributes = [];
        this.filters = {};
        this.quickStep = 'storage';
        this.cancelSuggestionRequest();
        this.open = false;
        await this.loadModels(brand.id);
    },

    async selectBrandId(brandId) {
        this.selectedBrandId = brandId || '';
        this.selectedModelId = '';
        this.selectedModelLabel = '';
        this.query = '';
        this.searchQuery = '';
        this.cancelAttributeRequest();
        this.attributes = [];
        this.filters = {};
        this.quickStep = 'storage';
        await this.loadModels(this.selectedBrandId);
    },

    async selectModel(model) {
        const previousModelId = this.selectedModelId;
        this.selectedBrandId = model.brand_id;
        this.selectedModelId = model.id;
        this.selectedModelLabel = model.label;
        this.query = model.label;
        this.searchQuery = model.label;
        if (previousModelId !== model.id) this.filters = {};
        this.quickStep = 'storage';
        this.open = false;
        await this.loadAttributes();
    },

    async selectModelId(modelId) {
        const model = this.models.find((item) => String(item.id) === String(modelId));
        this.selectedModelId = modelId;
        this.selectedBrandId = model?.brand_id || '';
        this.selectedModelLabel = model?.label || this.query;
        this.query = this.selectedModelLabel;
        this.searchQuery = this.query;
        this.filters = {};
        this.quickStep = 'storage';
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
            const params = new URLSearchParams({ active_only: this.activeOnly ? '1' : '0' });
            Object.entries(this.filters || {}).forEach(([attributeId, value]) => {
                if (Array.isArray(value)) value.forEach((item) => item && params.append(`filters[${attributeId}][]`, item));
                else if (value !== null && value !== '') params.set(`filters[${attributeId}]`, value);
            });
            const response = await fetch(`${this.attributesEndpoint}/${this.selectedModelId}/attributes?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (!response.ok) throw new Error('Attribute request failed');
            this.attributes = await response.json();
            this.syncQuickStep();
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

    async attributeChanged() {
        await this.loadAttributes();
    },

    syncQuickStep() {
        const storage = this.attributes.find((attribute) => attribute.slug === 'storage');
        const color = this.attributes.find((attribute) => attribute.slug === 'color');
        if (color && this.filters[color.id] !== undefined && this.filters[color.id] !== '') {
            this.quickStep = 'done';
        } else if (this.quickStep === 'color' && storage && this.filters[storage.id] === '') {
            // “همه حافظه‌ها” is a valid choice; keep the flow on the color step.
            this.quickStep = 'color';
        } else if (storage && this.filters[storage.id] !== undefined && this.filters[storage.id] !== '') {
            this.quickStep = 'color';
        } else {
            this.quickStep = 'storage';
        }
    },

    quickAttribute(slug) {
        return this.attributes.find((attribute) => attribute.slug === slug);
    },

    quickOptions(slug) {
        const attribute = this.quickAttribute(slug);
        return attribute ? this.attributeOptions(attribute) : [];
    },

    selectQuickAttribute(attribute, value) {
        this.filters[attribute.id] = value;
        if (attribute.slug === 'storage') {
            this.quickStep = 'color';
            this.query = `${this.selectedModelLabel} · ${value}${attribute.unit ? ` ${attribute.unit}` : ''}`;
            this.loadAttributes();
            this.open = true;
            return;
        }

        this.quickStep = 'done';
        const storage = this.quickAttribute('storage');
        const storageValue = storage ? this.filters[storage.id] : '';
        this.query = `${this.selectedModelLabel}${storageValue ? ` · ${storageValue} GB` : ''} · ${value}`;
        this.open = false;
    },

    reset() {
        this.query = '';
        this.searchQuery = '';
        this.selectedBrandId = '';
        this.selectedModelId = '';
        this.selectedModelLabel = '';
        this.modelsController?.abort();
        this.modelsController = null;
        this.modelsLoading = false;
        this.models = [];
        this.provinceId = '';
        this.cityId = '';
        this.cityIds = [];
        this.minPrice = this.priceFloor;
        this.maxPrice = this.priceCeiling;
        this.sort = 'newest';
        this.activeOnly = true;
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
        this.quickStep = 'storage';
        this.suggestions = { brands: [], models: [] };
        this.cancelSuggestionRequest();
        this.open = false;
        this.loadModels();
    },

    toggleCity(cityId) {
        const id = String(cityId);
        this.cityIds = this.cityIds.includes(id)
            ? this.cityIds.filter((item) => item !== id)
            : [...this.cityIds, id];
        this.cityId = this.cityIds[0] || '';
    },

    clearCities() {
        this.cityIds = [];
        this.cityId = '';
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
    titleInput: null,
    titleAttributes: [],
    titleLanguage: 'fa',

    init() {
        const root = this.$root;
        this.modelsEndpoint = root.dataset.modelsEndpoint || '';
        this.attributesEndpoint = this.modelsEndpoint;
        this.citiesEndpoint = root.dataset.citiesEndpoint || '';
        this.brandId = root.dataset.initialBrandId || '';
        this.modelId = root.dataset.initialModelId || '';
        this.provinceId = root.dataset.initialProvinceId || '';
        this.cityId = root.dataset.initialCityId || '';
        this.titleLanguage = root.dataset.titleLanguage || 'fa';
        this.titleInput = root.querySelector('[data-auto-listing-title]');
        if (this.titleInput) {
            this.titleInput.addEventListener('input', () => {
                this.titleInput.dataset.autoListingTitle = 'false';
            });
        }
        window.addEventListener('listing-attributes-changed', (event) => {
            this.titleAttributes = event.detail?.definitions || this.titleAttributes;
            this.updateGeneratedTitle(event.detail?.rows || []);
        });
        const attributesNode = root.querySelector('[data-listing-initial-attributes]');
        const valuesNode = root.querySelector('[data-listing-initial-values]');
        try { this.attributes = attributesNode ? JSON.parse(attributesNode.textContent || '[]') : []; } catch { this.attributes = []; }
        this.titleAttributes = this.attributes;
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
            this.updateGeneratedTitle();
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
            const response = await fetch(`${this.attributesEndpoint}/${this.modelId}/attributes?all=1`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Attribute request failed');
            this.attributes = await response.json();
            this.titleAttributes = this.attributes;
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

    updateGeneratedTitle(rows = []) {
        if (!this.titleInput || this.titleInput.dataset.autoListingTitle !== 'true') return;
        const title = window.listingTitleGenerator?.generate({
            root: this.$root,
            models: this.models,
            modelId: this.modelId,
            definitions: this.titleAttributes,
            rows,
            language: this.titleLanguage,
        });
        if (title) this.titleInput.value = title;
    },
});

window.moneyInput = (initial = '') => ({
    raw: '',
    display: '',

    init() {
        this.setRaw(initial);
    },

    normalize(value) {
        return String(value ?? '')
            .replace(/[۰-۹٠-٩]/g, (digit) => {
                const persianIndex = '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit);
                return persianIndex >= 0 ? String(persianIndex) : String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
            })
            .replace(/\D/g, '');
    },

    setRaw(value) {
        this.raw = this.normalize(value);
        this.display = this.raw ? Number(this.raw).toLocaleString('fa-IR') : '';
    },

    formatInput(event) {
        const cursorValue = this.normalize(event.target.value);
        this.raw = cursorValue;
        this.display = cursorValue ? Number(cursorValue).toLocaleString('fa-IR') : '';
    },
});

window.favoriteToggle = (endpoint, initialFavorited = false, authenticated = false) => ({
    endpoint,
    favorited: initialFavorited,
    authenticated,
    loading: false,

    toggle() {
        if (!this.authenticated) {
            window.dispatchEvent(new CustomEvent('show-notification', { detail: { title: 'ورود لازم است', message: 'برای افزودن آگهی به علاقه‌مندی‌ها باید وارد سایت شوید.', tone: 'info' } }));

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

const showDashboardNotification = (title, message, tone = 'info') => {
    window.dispatchEvent(new CustomEvent('show-notification', { detail: { title, message, tone } }));
};

const formatDashboardPrice = (value) => new Intl.NumberFormat('fa-IR').format(Number(value || 0));

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || (!form.action.includes('/quick-update') && !form.action.includes('/toggle-disabled'))) return;

    event.preventDefault();
    const submitButton = form.querySelector('button[type="submit"], button:not([type])');
    if (submitButton?.disabled) return;
    if (submitButton) submitButton.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: new FormData(form),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'عملیات انجام نشد؛ دوباره تلاش کنید.');

        const listingId = form.dataset.quickUpdateListing;
        const card = form.closest('article') || (listingId
            ? document.querySelector('[data-listing-card="' + CSS.escape(String(listingId)) + '"]')
            : null);
        if (form.action.includes('/quick-update')) {
            const priceNode = card?.querySelector('[data-listing-price]');
            if (priceNode) priceNode.textContent = data.price_on_request ? 'تماس بگیرید' : formatDashboardPrice(data.price) + ' تومان';
            form.querySelector('input[name="price"]')?.setAttribute('value', data.price ?? '');
            form.closest('[role="dialog"]')?.querySelector('[data-modal-hide]')?.click();
            showDashboardNotification('آگهی به‌روزرسانی شد', data.message || 'قیمت آگهی ذخیره شد.', 'success');
        } else {
            const toggleButton = form.querySelector('button');
            if (toggleButton) toggleButton.textContent = data.is_user_disabled ? 'فعال‌سازی' : 'غیرفعال‌کردن';
            const statusNode = card?.querySelector('h2')?.parentElement?.querySelector('span');
            if (statusNode) statusNode.textContent = data.is_user_disabled ? 'غیرفعال' : 'فعال';
            showDashboardNotification('وضعیت آگهی تغییر کرد', data.message || '', 'success');
        }
    } catch (error) {
        showDashboardNotification('خطا در عملیات', error.message || 'عملیات انجام نشد؛ دوباره تلاش کنید.', 'error');
    } finally {
        if (submitButton) submitButton.disabled = false;
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.action.includes('/dashboard/listings/bulk')) return;

    event.preventDefault();
    const submitButton = form.querySelector('button[type="submit"], button:not([type])');
    if (submitButton?.disabled) return;
    if (submitButton) submitButton.disabled = true;

    const formData = new FormData(form);
    const requestedAction = String(formData.get('action') || '');
    if (requestedAction === 'delete' && !window.confirm('آگهی‌های انتخاب‌شده حذف شوند؟ این عملیات قابل بازگشت نیست.')) {
        if (submitButton) submitButton.disabled = false;
        return;
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: formData,
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'عملیات گروهی انجام نشد؛ دوباره تلاش کنید.');

        const listingIds = (data.listing_ids || []).map((id) => String(id));
        listingIds.forEach((id) => {
            const checkbox = document.querySelector('input[name="listing_ids[]"][value="' + CSS.escape(id) + '"]');
            const card = checkbox?.closest('article');
            if (!card) return;

            if (data.action === 'delete') {
                card.remove();
                return;
            }

            if (data.action === 'price' || data.action === 'contact_price') {
                const priceNode = card.querySelector('[data-listing-price]');
                const priceState = data.prices?.[id];
                if (priceNode) {
                    priceNode.textContent = data.action === 'contact_price' || priceState?.price_on_request
                        ? 'تماس بگیرید'
                        : formatDashboardPrice(priceState?.price ?? data.price) + ' تومان';
                }
            }

            if (data.action === 'disable' || data.action === 'enable') {
                const statusNode = card.querySelector('h2')?.parentElement?.querySelector('span');
                const toggleButton = card.querySelector('form[action*="/toggle-disabled"] button');
                if (statusNode) statusNode.textContent = data.action === 'disable' ? 'غیرفعال' : 'فعال';
                if (toggleButton) toggleButton.textContent = data.action === 'disable' ? 'فعال‌سازی' : 'غیرفعال‌کردن';
            }

            if (data.action === 'sold') {
                const statusNode = card.querySelector('h2')?.parentElement?.querySelector('span');
                const statusForm = card.querySelector('[data-listing-status-form]');
                const restoreAction = statusForm?.dataset.restoreAction;
                if (statusNode) statusNode.textContent = 'فروخته‌شده';
                if (statusForm && restoreAction) {
                    statusForm.action = restoreAction;
                    const statusButton = statusForm.querySelector('button');
                    if (statusButton) statusButton.textContent = 'بازگردانی به منتشرشده';
                }
            }
        });

        form.querySelector('[data-modal-hide]')?.click();
        window.dispatchEvent(new CustomEvent('dashboard-bulk-complete'));
        showDashboardNotification('عملیات گروهی انجام شد', data.message || '', 'success');
    } catch (error) {
        showDashboardNotification('خطا در عملیات گروهی', error.message || 'عملیات انجام نشد؛ دوباره تلاش کنید.', 'error');
    } finally {
        if (submitButton) submitButton.disabled = false;
    }
});

window.dashboardBulkActions = (visibleIds = [], matchingIds = []) => ({
    visibleIds,
    matchingIds,
    selectedIds: [],
    allMatching: false,

    init() {
        window.addEventListener('dashboard-bulk-complete', () => this.clearSelection());
    },

    toggleVisible(event) {
        this.allMatching = false;
        this.selectedIds = event.target.checked ? [...this.visibleIds] : [];
    },

    selectAllMatching() {
        this.allMatching = true;
        this.selectedIds = [...this.matchingIds];
    },

    clearSelection() {
        this.allMatching = false;
        this.selectedIds = [];
    },

    selectForPrice(id) {
        this.allMatching = false;
        this.selectedIds = [String(id)];
    },

    isVisibleSelected() {
        return this.visibleIds.length > 0 && this.visibleIds.every((id) => this.selectedIds.includes(id));
    },

    selectedOutsidePage() {
        return this.selectedIds.filter((id) => !this.visibleIds.includes(id));
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

const updateUnreadNotificationBadges = (count) => {
    document.querySelectorAll('[data-unread-notifications-badge]').forEach((badge) => {
        const value = Number(count) || 0;
        badge.textContent = value > 9 ? '۹+' : String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
        badge.setAttribute('aria-label', `${value} اعلان خوانده‌نشده`);
        badge.classList.toggle('hidden', value === 0);
    });
};

const markNotificationReadInDom = (article) => {
    if (!article) return;
    article.classList.remove('border-primary/20', 'bg-primary-50');
    article.classList.add('border-slate-200', 'bg-white');
    const icon = article.querySelector('div.flex.items-start > span');
    icon?.classList.remove('bg-primary', 'text-white');
    icon?.classList.add('bg-slate-100', 'text-slate-400');
    article.querySelector('[data-notification-unread]')?.remove();
    article.querySelector('[data-notification-read]')?.remove();
};

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;

    const readAll = form.matches('[data-notifications-read-all]');
    const readOne = form.matches('[data-notification-read]');
    if (!readAll && !readOne) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"], button:not([type])');
    if (button?.disabled) return;
    if (button) button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: new FormData(form),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'تغییر وضعیت اعلان انجام نشد؛ دوباره تلاش کنید.');

        if (readAll) {
            document.querySelectorAll('[data-notification-id]').forEach(markNotificationReadInDom);
            form.remove();
        } else {
            markNotificationReadInDom(form.closest('[data-notification-id]'));
        }

        updateUnreadNotificationBadges(data.unread_count);
        window.dispatchEvent(new CustomEvent('show-notification', { detail: { title: 'اعلان‌ها به‌روزرسانی شد', message: data.message, tone: 'success' } }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('show-notification', { detail: { title: 'خطا در عملیات اعلان', message: error.message || 'دوباره تلاش کنید.', tone: 'error' } }));
    } finally {
        if (button) button.disabled = false;
    }
});

window.initInfiniteListingFeeds = (root = document) => {
    root.querySelectorAll('[data-listing-feed][data-pagination-mode="infinite"]').forEach((feed) => {
        if (feed.dataset.infiniteReady === 'true') return;

        const sentinel = feed.querySelector('[data-infinite-sentinel]');
        const skeleton = feed.querySelector('[data-infinite-skeleton]');
        const items = feed.querySelector('[data-listing-items]');
        if (!sentinel || !items) return;

        const setSkeletonVisibility = (visible) => {
            if (!skeleton) return;
            skeleton.hidden = !visible;
            skeleton.classList.toggle('hidden', !visible);
        };

        setSkeletonVisibility(false);

        let loading = false;
        const loadMore = async () => {
            const nextPageUrl = feed.dataset.nextPageUrl;
            if (loading || !nextPageUrl) return;

            loading = true;
            feed.dataset.loading = 'true';
            setSkeletonVisibility(true);

            try {
                const url = new URL(nextPageUrl, window.location.href);
                url.searchParams.set('infinite', '1');
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Infinite listing request failed');
                const data = await response.json();

                if (data.html) items.insertAdjacentHTML('beforeend', data.html);
                feed.dataset.nextPageUrl = data.next_page_url || '';
                if (!data.next_page_url) {
                    observer.disconnect();
                    sentinel.remove();
                }
                window.refreshFlowbiteTooltips?.();
            } catch (error) {
                window.dispatchEvent(new CustomEvent('show-notification', {
                    detail: {
                        title: 'خطا در بارگذاری آگهی‌ها',
                        message: 'بارگذاری ادامه آگهی‌ها انجام نشد؛ دوباره تلاش کنید.',
                        tone: 'error',
                    },
                }));
            } finally {
                loading = false;
                feed.dataset.loading = 'false';
                setSkeletonVisibility(false);
            }
        };

        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) loadMore();
        }, { rootMargin: '500px 0px' });

        if (feed.dataset.nextPageUrl) observer.observe(sentinel);
        else sentinel.remove();
        feed.dataset.infiniteReady = 'true';
    });
};

Alpine.start();
window.initInfiniteListingFeeds();
