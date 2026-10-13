import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import $ from 'jquery';
import './listing-attribute-repeater';
import './admin-listing-form';
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
    invalid: false,
    message: '',
    primaryIndex: '0',
    primaryImageId: initialPrimaryImageId ? String(initialPrimaryImageId) : '',

    setPrimary(index = '', imageId = '') {
        this.primaryIndex = imageId ? '' : String(index);
        this.primaryImageId = imageId ? String(imageId) : '';
    },

    select(event) {
        this.revokePreviews();
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
        this.previews = validFiles.map((file) => ({ name: file.name, url: URL.createObjectURL(file) }));
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
