const toPersianDigits = (value) => String(value).replace(/[0-9]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);

const configuredMaxMegabytes = (input) => {
    const localValue = Number(input.dataset.maxImageMb || input.form?.dataset.maxImageMb || 0);
    if (localValue > 0) return localValue;

    const metaValue = Number(document.querySelector('meta[name="max-image-upload-mb"]')?.content || 0);
    return metaValue > 0 ? metaValue : 5;
};

const removeOversizedFiles = (input, validFiles) => {
    if (!input.multiple || validFiles.length === 0) {
        input.value = '';
        return;
    }

    try {
        const transfer = new DataTransfer();
        validFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    } catch {
        input.value = '';
    }
};

const setInlineFileError = (input, message) => {
    input.setCustomValidity(message);
    input.classList.add('ring-2', 'ring-error');

    const container = input.closest('[data-file-upload]') || input.parentElement;
    if (!container) return;

    let error = container.querySelector('[data-file-size-error]');
    if (!error) {
        error = document.createElement('p');
        error.dataset.fileSizeError = 'true';
        error.className = 'mt-2 text-xs font-bold text-error';
        container.append(error);
    }
    error.textContent = message;
    error.hidden = false;
};

const clearInlineFileError = (input) => {
    input.setCustomValidity('');
    input.classList.remove('ring-2', 'ring-error');
    input.closest('[data-file-upload]')?.querySelector('[data-file-size-error]')?.remove();
};

const announceFileError = (message) => {
    const detail = {
        title: 'فایل انتخاب‌شده مجاز نیست',
        message,
        tone: 'error',
    };

    window.dispatchEvent(new CustomEvent('show-notification', { detail }));
    window.dispatchEvent(new CustomEvent('admin-notification', { detail }));
};

const validateImageInput = (input) => {
    const files = [...(input.files || [])];
    if (!files.length) {
        clearInlineFileError(input);
        return true;
    }

    const maxMegabytes = configuredMaxMegabytes(input);
    const maxBytes = maxMegabytes * 1024 * 1024;
    const oversized = files.filter((file) => file.size > maxBytes);
    if (!oversized.length) {
        clearInlineFileError(input);
        return true;
    }

    const validFiles = files.filter((file) => file.size <= maxBytes);
    removeOversizedFiles(input, validFiles);

    const fileNames = oversized.map((file) => file.name).join('، ');
    const message = `حجم ${fileNames} بیشتر از ${toPersianDigits(maxMegabytes)} مگابایت است. فایل بزرگ حذف شد.`;
    setInlineFileError(input, message);
    announceFileError(message);

    return false;
};

// Capture the change before Alpine or page-specific preview handlers receive it,
// so oversized files never enter the preview or submit payload.
document.addEventListener('change', (event) => {
    const input = event.target;
    if (input instanceof HTMLInputElement && input.type === 'file') validateImageInput(input);
}, true);

window.validateImageInput = validateImageInput;
