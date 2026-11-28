<?php

use App\Models\PhoneModel;

return [
    ['داشبورد', 'home', 'admin.dashboard'],
    ['کاربران', 'users', [
        ['همهٔ کاربران', 'admin.users.index', 'admin.users.*'],
        ['نقش‌ها و مجوزها', 'admin.roles.index', 'admin.roles.*'],
        ['سوابق فعالیت'],
    ]],
    ['فروشگاه‌ها', 'building-storefront', [
        ['همهٔ فروشگاه‌ها', 'admin.storefronts.index', 'admin.storefronts.*'],
        ['پرونده و احراز هویت'],
        ['شعب و کارکنان'],
    ]],
    ['آگهی‌ها', 'clipboard-document-list', [
        ['مدیریت آگهی‌ها', 'admin.listings.index', 'admin.listings.*'],
        ['ایمپورت آگهی', 'admin.listing-imports.index', 'admin.listing-imports.index'],
        ['تست ایمپورت آگهی', 'admin.listing-imports.test.create', 'admin.listing-imports.test*'],
        ['اصطلاحات ایمپورت', 'admin.listing-imports.terms.index', 'admin.listing-imports.terms.*'],
        ['رسانه‌ها', 'admin.media.index', 'admin.media.*'],
        ['قوانین انتشار و سهمیهٔ فروشگاه‌ها'],
    ]],
    ['کاتالوگ محصولات', 'rectangle-stack', [
        ...array_values(array_map(
            static fn ($type, $label) => ['کاتالوگ '.$label, 'admin.brands.index', ['admin.brands.*', 'admin.phone-series.*', 'admin.phone-models.*'], ['device_type' => $type]],
            array_keys(PhoneModel::DEVICE_TYPES),
            PhoneModel::DEVICE_TYPES,
        )),
        ['ویژگی‌های محصول', 'admin.attributes.index', 'admin.attributes.*'],
    ]],
    ['بسته‌ها و اشتراک‌ها', 'cube', [
        ['بسته‌ها و سهمیهٔ آگهی', 'admin.listing-packages.index', 'admin.listing-packages.*'],
        ['تمدید و انقضا'],
    ]],
    ['مدیریت مالی', 'banknotes', [['سفارش‌ها و پرداخت‌ها'], ['کیف پول و دفتر مالی'], ['فاکتورها'], ['بازپرداخت و مغایرت']]],
    ['بازاریابی و تبلیغات', 'megaphone', [['تخفیف و کمپین'], ['آگهی ویژه و نردبان'], ['بنر و جایگاه نمایش']]],
    ['اعتبار و رتبه‌بندی', 'star', [['نظرات و امتیازها'], ['درجات و نشان‌ها'], ['رتبه‌بندی طبیعی']]],
    ['هوش مصنوعی و جستجو', 'magnifying-glass', [['ثبت آگهی صوتی'], ['پیشنهاد و جستجوی هوشمند']]],
    ['مدیریت محتوا', 'document-text', [['صفحات و بنرهای محتوایی'], ['راهنما و سئو']]],
    ['پشتیبانی و شکایات', 'chat-bubble-left-right', [
        ['گزارش‌های تخلف', 'admin.reports.index', 'admin.reports.*'],
        ['تیکت‌ها و شکایات'],
    ]],
    ['مرکز اعلان‌ها', 'bell', [['اعلان‌های مدیریت', 'admin.notifications.index', 'admin.notifications.*'], ['قالب‌ها و تاریخچهٔ ارسال']]],
    ['گزارش‌ها و تحلیل داده', 'chart-bar', [['گزارش‌های مالی'], ['عملکرد فروشگاه‌ها و آگهی‌ها']]],
    ['امنیت و سطح دسترسی', 'shield-check', [['مدیران و مجوزهای عملیاتی'], ['سوابق مدیریتی و امنیتی']]],
    ['تنظیمات و زیرساخت', 'cog-6-tooth', [['تنظیمات عمومی', 'admin.settings.edit', 'admin.settings.*'], ['استان‌ها و شهرها', 'admin.locations.index', 'admin.locations.*'], ['سرویس‌ها و وضعیت سامانه']]],
    ['مشاهده سایت', 'globe-alt', 'home'],
];
