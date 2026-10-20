const englishColors = {
    'مشکی': 'Black', 'سفید': 'White', 'طلایی': 'Gold', 'نقره‌ای': 'Silver',
    'آبی': 'Blue', 'سبز': 'Green', 'بنفش': 'Purple', 'خاکستری': 'Gray',
    'قرمز': 'Red', 'صورتی': 'Pink', 'نارنجی': 'Orange', 'سرمه‌ای': 'Navy',
    'مسی': 'Copper', 'تیفانی': 'Tiffany', 'کرم': 'Cream', 'زرد': 'Yellow',
};

const englishDigits = (value) => String(value ?? '').replace(/[۰-۹٠-٩]/g, (digit) => {
    const code = digit.charCodeAt(0);
    return String(code >= 0x06f0 ? code - 0x06f0 : code - 0x0660);
});

const attributeValue = (definitions, values, slug) => {
    const definition = definitions.find((item) => item.slug === slug);
    if (!definition) return '';
    const value = values[String(definition.id)];
    return Array.isArray(value) ? value.filter(Boolean).join('، ') : (value ?? '');
};

window.listingTitleGenerator = {
    generate({ root, models = [], modelId = '', definitions = [], rows = [], language = 'fa' }) {
        const english = language === 'en';
        const brandOption = root?.querySelector('[name="brand_id"] option:checked');
        const brand = english
            ? (brandOption?.dataset.nameEn || brandOption?.textContent?.match(/\(([^)]*)\)/)?.[1]?.trim() || '')
            : (brandOption?.textContent?.split('(')[0]?.trim() || '');
        const model = models.find((item) => String(item.id) === String(modelId));
        const modelName = english
            ? (model?.secondary || model?.name_en || model?.name || '')
            : (model?.label || model?.name_fa || model?.name || '');

        if (!brand || !modelName) return '';

        const values = Object.fromEntries((rows || []).map((row) => [String(row.attributeId), row.value]));
        const color = attributeValue(definitions, values, 'color');
        const storage = attributeValue(definitions, values, 'storage');
        const parts = [brand, modelName];

        if (color) parts.push(english ? (englishColors[color] || color) : color);
        if (storage) parts.push(english ? `${englishDigits(storage)} GB` : `${Number(storage).toLocaleString('fa-IR')} گیگابایت`);

        return parts.join(' ');
    },
};
