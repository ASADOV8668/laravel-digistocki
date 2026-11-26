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
