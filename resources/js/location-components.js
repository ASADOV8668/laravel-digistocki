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
