# دیجی استوکی

سامانه آگهی‌گذاری و خرید و فروش موبایل با Laravel، Blade، Tailwind CSS، Alpine.js و Flowbite.

## راهنمای نصب

### پیش‌نیازها

- PHP 8.2 یا بالاتر
- Composer
- MySQL
- Node.js و npm
- Apache یا Nginx

### نصب وابستگی‌ها

```bash
composer install
npm install
```

### تنظیم فایل محیطی

فایل نمونه را کپی کرده و مقادیر پایگاه داده و تنظیمات محیط را وارد کنید:

```bash
cp .env.example .env
php artisan key:generate
```

مقادیر اصلی محیط توسعه نمونه:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/app

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=app
DB_USERNAME=root
DB_PASSWORD=
```

### پایگاه داده و فایل‌های عمومی

```bash
php artisan migrate --seed
php artisan storage:link
```

در محیطی که داده‌های قبلی دارد، برای حفظ اطلاعات موجود فقط migrationها را اجرا کنید و seed را اجرا نکنید:

```bash
php artisan migrate --force
```

### ساخت assetها

برای توسعه:

```bash
npm run dev
```

برای محیط production:

```bash
npm run build
```

### اجرای پروژه

```bash
php artisan serve
```

سپس پروژه را در آدرس زیر باز کنید:

```text
http://127.0.0.1:8000
```

### پاک‌سازی cache در زمان استقرار

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## ربات مدیریت بله

بعد از استقرار نسخهٔ جدید روی سرور، `BALE_BOT_TOKEN` را در فایل `.env` سرور تنظیم کنید. برای `BALE_WEBHOOK_SECRET` یک مقدار مستقل با دستور زیر بسازید و خروجی را در همان فایل قرار دهید.

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

`APP_URL` باید آدرس عمومی HTTPS سایت باشد. برای دیجی استوکی این مقدار `https://digistocki.com` است. سپس اجرا کنید.

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan bale:register-webhook
```

ادمین در چت خصوصی ربات `/start` می‌فرستد و کد یک‌بارمصرف را در بخش «اتصال ربات بله» در داشبورد مدیریت وارد می‌کند. از همان صفحه می‌تواند اتصال حساب را قطع کند.

پس از اتصال، منوی ربات تعداد آگهی‌ها، فروشگاه‌ها و گزارش‌های در انتظار را نشان می‌دهد. با انتخاب «آگهی‌های منتظر» می‌توان صف را از قدیمی‌ترین آگهی مرور کرد. دکمه‌های تأیید و رد با دلیل آماده مرحلهٔ تأیید نهایی دارند.

- `/report` آمار امروز و موارد در انتظار رسیدگی را نشان می‌دهد.
- `/pending` ده آگهی در انتظار را به ترتیب قدیمی‌ترین نشان می‌دهد.
- `/approve 123` آگهی در انتظار را تأیید می‌کند.
- `/reject 123 علت رد` آگهی در انتظار را رد می‌کند و دلیل را به صاحب آگهی اطلاع می‌دهد.

گزارش روزانه ساعت ۲۰:۰۰ تهران برای ادمین‌های فعال و متصل ارسال می‌شود. برای ارسال خودکار، زمان‌بند لاراول روی سرور باید هر دقیقه `php artisan schedule:run` را اجرا کند. برای بررسی فوری ارسال، `php artisan bale:daily-report` را اجرا کنید.
