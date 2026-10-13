const digitMap = {
    '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
    '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
};

export const normalizePersianDigits = (value) => String(value ?? '').replace(/[۰-۹٠-٩]/g, (digit) => digitMap[digit]);

export const initPersianDigitInputs = () => {
    const normalize = (event) => {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) return;

        const value = field.value;
        const normalized = normalizePersianDigits(value);
        if (value === normalized) return;

        const start = field.selectionStart;
        const end = field.selectionEnd;
        field.value = normalized;
        if (document.activeElement === field && start !== null && end !== null) {
            field.setSelectionRange(start, end);
        }
    };

    document.addEventListener('input', normalize, true);
    document.addEventListener('change', normalize, true);
};

initPersianDigitInputs();
