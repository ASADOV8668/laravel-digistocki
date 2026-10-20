import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import $ from 'jquery';
import './listing-attribute-repeater';
import './admin-listing-form';
import './listing-title';
import './location-components';
import './persian-digits';
import '../vendor/persian-datepicker/persianDatepicker-default.css';

window.Alpine = Alpine;
window.Chart = Chart;
window.$ = window.jQuery = $;

window.adminToast = () => ({
    visible: false,
    title: '',
    message: '',
    timer: null,

    show(detail = {}) {
        this.title = detail.title || 'عملیات انجام شد';
        this.message = detail.message || '';
        this.visible = true;
        clearTimeout(this.timer);
        this.timer = window.setTimeout(() => { this.visible = false; }, 4500);
    },
});

window.attributeVisibilityPicker = (endpoint, selected = [], initialRule = 'all') => ({
    endpoint,
    models: [],
    selected: (selected || []).map((id) => String(id)),
    query: '',
    rule: initialRule || 'all',
    loading: false,
    error: '',
    timer: null,
    controller: null,

    init() {
        this.rule = initialRule || this.$root.querySelector('select[name="visibility_rule"]')?.value || 'all';
        this.$watch('query', () => this.scheduleLoad());
        this.$watch('rule', (value) => {
            if (value === 'all') {
                this.models = [];
                this.error = '';
                return;
            }

            this.loadModels();
        });

        if (this.rule !== 'all') this.loadModels();
    },

    scheduleLoad() {
        if (this.rule === 'all') return;
        clearTimeout(this.timer);
        this.timer = window.setTimeout(() => this.loadModels(), 250);
    },

    async loadModels() {
        if (this.rule === 'all') return;
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        this.error = '';

        const params = new URLSearchParams();
        const query = String(this.query || '').trim();
        if (query) params.set('q', query);
        this.selected.forEach((id) => params.append('selected_ids[]', id));

        try {
            const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            if (!response.ok) throw new Error('دریافت فهرست مدل‌ها انجام نشد.');
            this.models = await response.json();
        } catch (exception) {
            if (exception.name !== 'AbortError') {
                this.models = [];
                this.error = exception.message || 'دریافت فهرست مدل‌ها انجام نشد.';
            }
        } finally {
            this.loading = false;
        }
    },
});

window.attributeOptionRestrictionForm = (endpoint, options = []) => ({
    endpoint,
    options: (options || []).map(String),
    models: [],
    selectedModels: [],
    selectedOptions: [],
    query: '',
    loading: false,
    timer: null,
    controller: null,

    init() {
        this.loadModels();
        this.$watch('query', () => {
            clearTimeout(this.timer);
            this.timer = window.setTimeout(() => this.loadModels(), 250);
        });
    },

    async loadModels() {
        this.controller?.abort();
        this.controller = new AbortController();
        this.loading = true;
        const params = new URLSearchParams();
        const query = String(this.query || '').trim();
        if (query) params.set('q', query);

        try {
            const response = await fetch(`${this.endpoint}?${params.toString()}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: this.controller.signal,
            });
            if (!response.ok) throw new Error('دریافت مدل‌ها انجام نشد.');
            this.models = await response.json();
        } catch (exception) {
            if (exception.name !== 'AbortError') this.models = [];
        } finally {
            this.loading = false;
        }
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
        const value = this.normalize(event.target.value);
        this.raw = value;
        this.display = value ? Number(value).toLocaleString('fa-IR') : '';
    },
});

window.adminPasswordGenerator = (initialPassword = '', initialConfirmation = '') => ({
    password: initialPassword || '',
    confirmation: initialConfirmation || '',
    visible: false,

    generate() {
        const groups = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnopqrstuvwxyz',
            '23456789',
            '!@#$%^&*_-+=',
        ];
        const all = groups.join('');
        const random = (max) => {
            if (window.crypto?.getRandomValues) {
                const values = new Uint32Array(1);
                window.crypto.getRandomValues(values);
                return values[0] % max;
            }
            return Math.floor(Math.random() * max);
        };
        const characters = groups.map((group) => group[random(group.length)]);
        while (characters.length < 16) characters.push(all[random(all.length)]);
        for (let index = characters.length - 1; index > 0; index--) {
            const swap = random(index + 1);
            [characters[index], characters[swap]] = [characters[swap], characters[index]];
        }
        this.password = characters.join('');
        this.confirmation = this.password;
        this.visible = true;
    },

    async copy() {
        if (!this.password) this.generate();
        try {
            await navigator.clipboard.writeText(this.password);
            window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'رمز عبور کپی شد', message: 'رمز عبور در کلیپ‌بورد قرار گرفت.' } }));
        } catch {
            window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'کپی انجام نشد', message: 'رمز را به‌صورت دستی انتخاب و کپی کنید.' } }));
        }
    },
});

const adminStatusClasses = {
    approved: 'bg-success/10 text-success',
    rejected: 'bg-error/10 text-error',
    pending: 'bg-warning/10 text-warning',
    sold: 'bg-info/10 text-info',
    expired: 'bg-slate-100 text-slate-500',
};

const showAdminSettingsFeedback = (form, message, tone = 'success') => {
    const feedback = form.querySelector('[data-admin-settings-feedback]');
    if (!feedback) return;
    feedback.className = 'rounded-2xl border px-4 py-3 text-sm font-bold ' + (tone === 'error' ? 'border-error/20 bg-error/10 text-error' : 'border-success/20 bg-success/10 text-success');
    feedback.textContent = message;
};

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.matches('[data-admin-settings-form]')) return;

    event.preventDefault();
    const button = form.querySelector('button[type="submit"], button:not([type])');
    if (button?.disabled) return;
    if (button) button.disabled = true;
    const originalText = button?.textContent;
    if (button) button.textContent = 'در حال ذخیره...';
    const feedback = form.querySelector('[data-admin-settings-feedback]');
    if (feedback) feedback.classList.add('hidden');

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
        if (!response.ok) {
            const errors = Object.values(data.errors || {}).flat();
            throw new Error(errors[0] || data.message || 'ذخیره تنظیمات انجام نشد؛ دوباره تلاش کنید.');
        }

        showAdminSettingsFeedback(form, data.message || 'تنظیمات سیستم ذخیره شد.');
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'تنظیمات ذخیره شد', message: data.message, tone: 'success' } }));
    } catch (error) {
        showAdminSettingsFeedback(form, error.message || 'ذخیره تنظیمات انجام نشد؛ دوباره تلاش کنید.', 'error');
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'خطا در تنظیمات', message: error.message || 'دوباره تلاش کنید.', tone: 'error' } }));
    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = originalText;
        }
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.action.match(/\/admin\/listings\/\d+\/(approve|reject)$/)) return;

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
        if (!response.ok) throw new Error(data.message || 'تغییر وضعیت آگهی انجام نشد؛ دوباره تلاش کنید.');

        const item = form.closest('tr, article');
        const statusNode = item?.querySelector('td:nth-child(3) span') || item?.querySelector('h2')?.parentElement?.querySelector('span');
        if (statusNode) {
            const compact = item.tagName.toLowerCase() === 'article';
            statusNode.className = (compact ? 'shrink-0 rounded-full px-2 py-1 text-[10px] font-black' : 'rounded-full px-3 py-1.5 text-[11px] font-black') + ' ' + (adminStatusClasses[data.status] || adminStatusClasses.pending);
            statusNode.textContent = data.status_label || data.status;
        }

        form.remove();
        const actionContainer = item?.querySelector('td:last-child > div') || item?.querySelector(':scope > div.mt-4');
        if (actionContainer && !actionContainer.querySelector('form')) {
            const notice = document.createElement('span');
            notice.className = 'rounded-lg bg-success/10 px-3 py-2 text-xs font-bold text-success';
            notice.textContent = 'ذخیره شد';
            actionContainer.append(notice);
        }
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'وضعیت آگهی تغییر کرد', message: data.message, tone: 'success' } }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'خطا در عملیات', message: error.message || 'دوباره تلاش کنید.', tone: 'error' } }));
    } finally {
        if (button) button.disabled = false;
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.action.match(/\/admin\/storefronts\/\d+\/(approve|reject|toggle)$/)) return;

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
        if (!response.ok) throw new Error(data.message || 'تغییر وضعیت فروشگاه انجام نشد؛ دوباره تلاش کنید.');

        const item = form.closest('article');
        const statusNode = item?.querySelector('span.rounded-full');
        if (statusNode) {
            const statusClass = data.status_label === 'فعال'
                ? 'bg-success/10 text-success'
                : data.status_label === 'مسدود توسط مدیریت' || data.status_label === 'ردشده'
                    ? 'bg-error/10 text-error'
                    : data.status_label === 'در انتظار بررسی'
                        ? 'bg-warning/10 text-warning'
                        : 'bg-slate-100 text-slate-500';
            statusNode.className = 'rounded-full px-3 py-1.5 text-[10px] font-black ' + statusClass;
            statusNode.textContent = data.status_label;
        }

        if (form.action.endsWith('/toggle')) {
            const toggleButton = form.querySelector('button');
            if (toggleButton) toggleButton.textContent = data.is_admin_disabled ? 'رفع مسدودی' : 'غیرفعال‌سازی مدیریتی';
        } else {
            form.remove();
            const actionContainer = item?.querySelector(':scope > div:last-child');
            if (actionContainer && !actionContainer.querySelector('form')) {
                const notice = document.createElement('span');
                notice.className = 'rounded-xl bg-success/10 px-3 py-2 text-xs font-bold text-success';
                notice.textContent = 'ذخیره شد';
                actionContainer.append(notice);
            }
        }

        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'وضعیت فروشگاه تغییر کرد', message: data.message, tone: 'success' } }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'خطا در عملیات فروشگاه', message: error.message || 'دوباره تلاش کنید.', tone: 'error' } }));
    } finally {
        if (button) button.disabled = false;
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.action.match(/\/admin\/users\/\d+\/(toggle-active|toggle-role|toggle-listing-permission)$/)) return;

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
        if (!response.ok) throw new Error(data.message || 'تغییر اطلاعات کاربر انجام نشد؛ دوباره تلاش کنید.');

        const item = form.closest('tr');
        const badges = item ? [...item.querySelectorAll('td:nth-child(2) span')] : [];
        const action = form.action.split('/').pop();
        const updateBadge = (badge, text, className) => {
            if (!badge) return;
            badge.className = 'rounded-full px-2.5 py-1 text-[10px] font-black ' + className;
            badge.textContent = text;
        };

        if (action === 'toggle-active') {
            updateBadge(badges[1], data.is_active ? 'فعال' : 'غیرفعال', data.is_active ? 'bg-success/10 text-success' : 'bg-error/10 text-error');
            if (button) button.textContent = data.is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی';
        } else if (action === 'toggle-role') {
            updateBadge(badges[0], data.role === 'admin' ? 'مدیر' : 'کاربر', data.role === 'admin' ? 'bg-accent/10 text-accent' : 'bg-slate-100 text-slate-600');
            if (button) button.textContent = data.role === 'admin' ? 'تبدیل به کاربر' : 'ارتقا به مدیر';
        } else {
            updateBadge(badges[2], data.can_post_listings ? 'ثبت آگهی مجاز' : 'ثبت آگهی مسدود', data.can_post_listings ? 'bg-info/10 text-info' : 'bg-warning/10 text-warning');
            if (button) button.textContent = data.can_post_listings ? 'بستن ثبت آگهی' : 'اجازه ثبت آگهی';
        }

        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'اطلاعات کاربر تغییر کرد', message: data.message, tone: 'success' } }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'خطا در عملیات کاربر', message: error.message || 'دوباره تلاش کنید.', tone: 'error' } }));
    } finally {
        if (button) button.disabled = false;
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.action.match(/\/admin\/reports\/\d+\/status$/)) return;

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
        if (!response.ok) throw new Error(data.message || 'تغییر وضعیت گزارش انجام نشد؛ دوباره تلاش کنید.');

        const item = form.closest('tr, article');
        const statusNode = item?.querySelector('td:nth-child(5) span') || item?.querySelector('article > div:first-child > span');
        const statusClasses = {
            resolved: 'bg-success/10 text-success',
            rejected: 'bg-error/10 text-error',
            reviewed: 'bg-info/10 text-info',
            pending: 'bg-warning/10 text-warning',
        };
        if (statusNode) {
            const compact = item.tagName.toLowerCase() === 'article';
            statusNode.className = (compact ? 'shrink-0 rounded-full px-2 py-1 text-[10px] font-black' : 'rounded-full px-3 py-1.5 text-[11px] font-black') + ' ' + (statusClasses[data.status] || statusClasses.pending);
            statusNode.textContent = data.status_label || data.status;
        }

        if (data.terminal) {
            const actionContainer = form.parentElement;
            form.remove();
            if (actionContainer && !actionContainer.querySelector('form')) {
                const notice = document.createElement('span');
                notice.className = 'text-xs font-bold text-slate-400';
                notice.textContent = 'وضعیت نهایی';
                actionContainer.append(notice);
            }
        } else {
            const select = form.querySelector('select[name="status"]');
            if (select) select.value = data.status;
        }

        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'وضعیت گزارش تغییر کرد', message: data.message, tone: 'success' } }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'خطا در عملیات گزارش', message: error.message || 'دوباره تلاش کنید.', tone: 'error' } }));
    } finally {
        if (button) button.disabled = false;
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented || !form.action.match(/\/admin\/(brands|phone-models|attributes)\/\d+\/toggle$/)) return;

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
        if (!response.ok) throw new Error(data.message || 'تغییر وضعیت کاتالوگ انجام نشد؛ دوباره تلاش کنید.');

        if (button) {
            const active = Boolean(data.is_active);
            button.className = 'rounded-full px-3 py-1.5 text-[11px] font-black transition ' + (active ? 'bg-success/10 text-success hover:bg-success/20' : 'bg-slate-100 text-slate-500 hover:bg-slate-200');
            button.textContent = data.status_label || (active ? 'فعال' : 'غیرفعال');
        }

        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'کاتالوگ به‌روزرسانی شد', message: data.message, tone: 'success' } }));
    } catch (error) {
        window.dispatchEvent(new CustomEvent('admin-notification', { detail: { title: 'خطا در عملیات کاتالوگ', message: error.message || 'دوباره تلاش کنید.', tone: 'error' } }));
    } finally {
        if (button) button.disabled = false;
    }
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

document.addEventListener('DOMContentLoaded', () => {
    const dateFields = $('[data-persian-datepicker]');
    if (dateFields.length) {
        import('../vendor/persian-datepicker/persianDatepicker.min.js').then(() => {
            dateFields.persianDatepicker({
                formatDate: 'YYYY/0M/DD',
                persianNumbers: true,
                isRTL: true,
                calendarPosition: { x: 0, y: 0 },
            });
        });
    }

    const dataElement = document.getElementById('listing-trend-data');
    const canvas = document.querySelector('[data-admin-chart="listing-trend"]');

    if (!dataElement || !canvas) return;

    const data = JSON.parse(dataElement.textContent);
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'آگهی‌های ثبت‌شده',
                data: data.values,
                borderColor: '#eb073f',
                backgroundColor: 'rgba(235, 7, 63, 0.12)',
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#eb073f',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            locale: 'fa-IR',
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: { rtl: true, textDirection: 'rtl', displayColors: false, padding: 12 },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { family: 'Vazirmatn' } } },
                y: { beginAtZero: true, ticks: { precision: 0, color: '#94a3b8', font: { family: 'Vazirmatn' } }, grid: { color: '#f1f5f9' } },
            },
        },
    });

    const statusElement = document.getElementById('listing-status-data');
    const statusCanvas = document.querySelector('[data-admin-chart="listing-status"]');
    if (!statusElement || !statusCanvas) return;
    const statusData = JSON.parse(statusElement.textContent);
    new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
            labels: statusData.labels,
            datasets: [{ data: statusData.values, backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#0ea5e9', '#94a3b8'], borderWidth: 0, hoverOffset: 5 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true, padding: 16, font: { family: 'Vazirmatn' } } }, tooltip: { rtl: true, textDirection: 'rtl' } },
        },
    });
});

Alpine.start();
