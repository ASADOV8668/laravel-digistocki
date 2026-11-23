export function installCameraListingLinks() {
    let press;
    let timer;
    let heldLink;

    const cancel = () => {
        clearTimeout(timer);
        press = null;
    };

    document.addEventListener('pointerdown', (event) => {
        const link = event.target.closest('[data-camera-listing-url]');
        if (!link || event.button !== 0 || !event.isPrimary) return;

        heldLink = null;
        press = { id: event.pointerId, x: event.clientX, y: event.clientY };
        timer = setTimeout(() => {
            heldLink = link;
            cancel();
            window.location.assign(link.dataset.cameraListingUrl);
        }, 2000);
    });
    document.addEventListener('pointermove', (event) => {
        if (press?.id === event.pointerId && Math.hypot(event.clientX - press.x, event.clientY - press.y) > 12) cancel();
    }, { passive: true });
    for (const eventName of ['pointerup', 'pointercancel']) {
        document.addEventListener(eventName, (event) => {
            if (press?.id === event.pointerId) cancel();
        });
    }
    window.addEventListener('blur', cancel);
    document.addEventListener('scroll', cancel, { capture: true, passive: true });
    document.addEventListener('contextmenu', (event) => {
        if (press && event.target.closest('[data-camera-listing-url]')) event.preventDefault();
    });
    document.addEventListener('click', (event) => {
        if (heldLink && event.target.closest('[data-camera-listing-url]') === heldLink) event.preventDefault();
    }, true);
}

export function cameraListing() {
    return {
        cameraMode: false,
        cameraOpened: false,
        cameraReady: false,
        cameraBusy: false,
        cameraError: '',
        cameraStep: null,
        cameraDeviceType: '',
        cameraDetailsStep: 'price',
        cameraEditingDetail: false,
        cameraSubmitting: false,
        cameraStepBusy: false,
        cameraCarryValues: null,
        cameraSkipped: {},
        cameraStream: null,

        init() {
            this.cameraMode = this.$root.hasAttribute('data-camera-listing');
            if (this.$root.dataset.cameraListing === 'auto') {
                this.$nextTick(() => this.openListingCamera());
            }
        },

        async openListingCamera() {
            if (this.cameraBusy || this.cameraOpened) return;
            this.cameraError = '';
            if (!navigator.mediaDevices?.getUserMedia || typeof DataTransfer === 'undefined') {
                this.cameraError = 'پیش‌نمایش زنده در این مرورگر در دسترس نیست. گزینهٔ «عکس با دوربین گوشی» را امتحان کنید.';
                return;
            }

            this.cameraBusy = true;
            try {
                this.cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } }, audio: false,
                });
                this.$refs.listingCameraVideo.srcObject = this.cameraStream;
                this.cameraOpened = true;
            } catch (error) {
                this.cameraError = error.name === 'NotAllowedError'
                    ? 'مرورگر اجازهٔ پیش‌نمایش زنده را نداد. دسترسی دوربین سایت را در تنظیمات مرورگر بررسی کنید یا «عکس با دوربین گوشی» را امتحان کنید.'
                    : 'پیش‌نمایش زنده باز نشد. گزینهٔ «عکس با دوربین گوشی» را امتحان کنید.';
            } finally {
                this.cameraBusy = false;
            }
        },

        closeListingCamera() {
            this.cameraStream?.getTracks().forEach((track) => track.stop());
            if (this.$refs.listingCameraVideo) this.$refs.listingCameraVideo.srcObject = null;
            this.cameraStream = null;
            this.cameraOpened = false;
            this.cameraReady = false;
        },

        takeListingPicture() {
            const video = this.$refs.listingCameraVideo;
            if (!video.videoWidth || this.cameraBusy) return;
            const canvas = document.createElement('canvas');
            const ratio = Math.min(1, 1600 / Math.max(video.videoWidth, video.videoHeight));
            canvas.width = Math.round(video.videoWidth * ratio);
            canvas.height = Math.round(video.videoHeight * ratio);
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            this.cameraBusy = true;
            canvas.toBlob((blob) => {
                this.cameraBusy = false;
                if (!blob) {
                    this.cameraError = 'ذخیرهٔ عکس انجام نشد. دوباره عکس بگیرید.';
                    return;
                }
                const files = new DataTransfer();
                files.items.add(new File([blob], 'product-camera.jpg', { type: 'image/jpeg' }));
                const input = this.$refs.listingCameraFile;
                input.files = files.files;
                this.useListingCameraFile({ target: input });
            }, 'image/jpeg', 0.8);
        },

        useListingCameraFile(event) {
            if (!event.target.files.length) return;
            this.cameraError = '';
            this.select(event);
            if (!this.previews.length) return;
            this.closeListingCamera();
            this.cameraStep = null;
            this.moveCameraListingForward();
        },

        cameraStageKey(stage) {
            return stage.type === 'attribute' ? String(stage.attribute.id) : stage.type;
        },

        cameraStageOptions(stage) {
            if (stage.attribute?.type === 'boolean') return [{ id: '1', label: 'بله' }, { id: '0', label: 'خیر' }];
            return this.fastStageOptions(stage);
        },

        cameraStageLabel(stage, value) {
            const option = this.cameraStageOptions(stage).find(({ id }) => String(id) === String(value));
            const label = (stage.type === 'model' ? option?.secondary : null) || option?.label || value;
            return stage.attribute?.unit === '%' ? `${label}٪` : label;
        },

        cameraStageAcceptsText(stage) {
            return ['string', 'integer', 'decimal'].includes(stage.attribute?.type);
        },

        cameraSummary(includeSkipped = false) {
            return this.fastStages().flatMap((stage) => {
                const key = this.cameraStageKey(stage);
                const value = this.fastStageValue(stage);
                const empty = value === '' || value == null || (Array.isArray(value) && !value.length);
                if (empty && !(includeSkipped && this.cameraSkipped[key])) return [];
                return [{
                    key, label: this.fastStageLabel(stage), value: empty ? '' : this.cameraStageLabel(stage, value),
                }];
            });
        },

        moveCameraListingForward() {
            if (!this.cameraDeviceType) {
                this.cameraStep = 'device';
                return;
            }
            if (!this.brandId) {
                this.cameraStep = 'brand';
                return;
            }
            const selected = new Set(this.cameraSummary().map(({ key }) => key));
            const next = this.fastStages().find((stage) => {
                const key = this.cameraStageKey(stage);
                const hasInput = this.cameraStageOptions(stage).length > 0 || this.cameraStageAcceptsText(stage);
                return !selected.has(key) && (stage.required || (hasInput && !this.cameraSkipped[key]));
            });
            this.cameraStep = next ? this.cameraStageKey(next) : this.fastComplete() ? null : 'brand';
        },

        async chooseCameraDeviceType(type) {
            if (this.cameraStepBusy) return;
            if (type !== this.cameraDeviceType) {
                this.cameraDeviceType = type;
                this.cameraCarryValues = null;
                this.cameraSkipped = {};
                this.fastController?.abort();
                await this.selectFastBrand({ id: '', label: '' });
            }
            this.moveCameraListingForward();
        },

        async chooseCameraBrand(brand) {
            if (this.cameraStepBusy) return;
            this.cameraStepBusy = true;
            try {
                if (String(brand.id) !== String(this.brandId) || !this.wizardConfig) {
                    this.cameraCarryValues = null;
                    this.cameraSkipped = {};
                    await this.selectFastBrand(brand);
                }
                this.moveCameraListingForward();
            } finally {
                this.cameraStepBusy = false;
            }
        },

        async chooseCameraStage(stage, option) {
            const empty = option.id === '' || (Array.isArray(option.id) && !option.id.length);
            if (this.cameraStepBusy || (stage.required && empty)) return;
            this.cameraStepBusy = true;
            try {
                if (stage.type === 'attribute') this.cameraSkipped[this.cameraStageKey(stage)] = empty;
                if (String(this.fastStageValue(stage)) !== String(option.id)) {
                    if (stage.type === 'series' || stage.type === 'model') {
                        this.cameraCarryValues ??= { ...this.values };
                        this.cameraSkipped = {};
                    }
                    await this.selectFastStage(stage, option);
                    if (stage.type === 'model' && this.wizardConfig && this.cameraCarryValues) {
                        const previous = this.cameraCarryValues;
                        this.cameraCarryValues = null;
                        this.values = Object.fromEntries(this.fastStages().flatMap((item) => {
                            if (!item.attribute) return [];
                            const id = item.attribute.id;
                            const value = previous[id];
                            if (value === '' || value == null) return [];
                            const options = this.cameraStageOptions(item);
                            if (!options.length && this.cameraStageAcceptsText(item)) return [[id, value]];
                            const isAvailable = (entry) => options.some(({ id }) => String(id) === String(entry));
                            if (Array.isArray(value)) {
                                const available = value.filter(isAvailable);
                                return available.length ? [[id, available]] : [];
                            }
                            return isAvailable(value) ? [[id, value]] : [];
                        }));
                        window.dispatchEvent(new CustomEvent('listing-attributes-loaded', { detail: { attributes: this.attributes, values: this.values } }));
                        this.updateGeneratedTitle();
                    }
                }
                this.moveCameraListingForward();
            } finally {
                this.cameraStepBusy = false;
            }
        },

        cameraSpecsComplete() {
            return Boolean(this.cameraDeviceType && this.wizardConfig) && this.previews.length > 0 && !this.cameraOpened && !this.cameraBusy && !this.fastLoading && this.fastComplete() && this.cameraStep === null && !this.cameraStepBusy;
        },

        cameraListingTitle() {
            const model = this.models.find(({ id }) => String(id) === String(this.modelId));
            const attributes = this.fastStages().flatMap(({ attribute }) => attribute ? [attribute] : []);
            return window.listingTitleGenerator.generateCamera(model, attributes, this.values);
        },

        editCameraDetail(field) {
            this.cameraEditingDetail = true;
            this.cameraDetailsStep = field;
        },

        finishCameraDetail(next) {
            this.cameraDetailsStep = this.cameraEditingDetail ? 'review' : next;
            this.cameraEditingDetail = false;
        },

        cameraCanSubmit() {
            return this.cameraSpecsComplete() && this.cameraDetailsStep === 'review';
        },

        submitCameraListing(event) {
            if (!this.cameraCanSubmit() || this.cameraSubmitting) {
                event.preventDefault();
                return;
            }
            this.cameraSubmitting = true;
        },
    };
}
