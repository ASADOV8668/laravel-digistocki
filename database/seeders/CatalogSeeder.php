<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Apple' => ['اپل', ['iPhone 11', 'iPhone 11 Pro', 'iPhone 11 Pro Max', 'iPhone SE 2', 'iPhone 12', 'iPhone 12 mini', 'iPhone 12 Pro', 'iPhone 12 Pro Max', 'iPhone 13', 'iPhone 13 mini', 'iPhone 13 Pro', 'iPhone 13 Pro Max', 'iPhone SE 3', 'iPhone 14', 'iPhone 14 Plus', 'iPhone 14 Pro', 'iPhone 14 Pro Max', 'iPhone 15', 'iPhone 15 Plus', 'iPhone 15 Pro', 'iPhone 15 Pro Max', 'iPhone 16', 'iPhone 16 Plus', 'iPhone 16 Pro', 'iPhone 16 Pro Max']],
            'Samsung' => ['سامسونگ', ['Galaxy S20', 'Galaxy S20 Plus', 'Galaxy S20 Ultra', 'Galaxy S21', 'Galaxy S21 Plus', 'Galaxy S21 Ultra', 'Galaxy S22', 'Galaxy S22 Plus', 'Galaxy S22 Ultra', 'Galaxy S23', 'Galaxy S23 Plus', 'Galaxy S23 Ultra', 'Galaxy S24', 'Galaxy S24 Plus', 'Galaxy S24 Ultra', 'Galaxy A12', 'Galaxy A13', 'Galaxy A14', 'Galaxy A15', 'Galaxy A24', 'Galaxy A34', 'Galaxy A54', 'Galaxy Z Flip 4', 'Galaxy Z Flip 5', 'Galaxy Z Fold 4', 'Galaxy Z Fold 5', 'Galaxy Note 20 Ultra']],
            'Xiaomi' => ['شیائومی', ['Redmi Note 10', 'Redmi Note 10 Pro', 'Redmi Note 11', 'Redmi Note 11 Pro', 'Redmi Note 11 Pro+', 'Redmi Note 12', 'Redmi Note 12 Pro', 'Redmi Note 12 Pro+', 'Redmi Note 13', 'Redmi Note 13 Pro', 'Redmi Note 13 Pro+', 'Redmi 10', 'Redmi 12', 'Redmi 13', 'POCO X3', 'POCO X4', 'POCO X5', 'POCO X6', 'POCO F3', 'POCO F4', 'POCO F5', 'Mi 11', 'Mi 12', 'Mi 13']],
            'Huawei' => ['هواوی', ['P30', 'P40', 'P50', 'Mate 30', 'Mate 40', 'Mate 50', 'Nova 9', 'Nova 10', 'Nova 11']],
            'Nokia' => ['نوکیا', ['G10', 'G20', 'G21', 'G50', 'X10', 'X20', 'X30']],
            'Sony' => ['سونی', ['Xperia 1', 'Xperia 5', 'Xperia 10']],
            'LG' => ['ال‌جی', ['V60', 'G8', 'K52']],
            'Google' => ['گوگل', ['Pixel 6', 'Pixel 6a', 'Pixel 7', 'Pixel 7a', 'Pixel 8', 'Pixel 8a', 'Pixel 9']],
            'OnePlus' => ['وان‌پلاس', ['OnePlus 8', 'OnePlus 8 Pro', 'OnePlus 9', 'OnePlus 9 Pro', 'OnePlus 10 Pro', 'OnePlus 11', 'OnePlus 12']],
            'Oppo' => ['اوپو', ['Reno 8', 'Reno 10', 'Find X5', 'Find X6']],
            'Vivo' => ['ویوو', ['V27', 'V29', 'X90']],
            'Realme' => ['ریلمی', ['C55', 'C67', '11 Pro', 'GT Neo 5']],
            'Asus' => ['ایسوس', ['ROG Phone 6', 'ROG Phone 7', 'Zenfone 10']],
            'Motorola' => ['موتورولا', ['Edge 30', 'Edge 40', 'Razr 40']],
        ];

        $attributes = $this->attributes();

        foreach ($brands as $nameEn => [$name, $models]) {
            $brand = Brand::updateOrCreate(
                ['slug' => Str::slug($nameEn)],
                ['name' => $name, 'name_en' => $nameEn, 'is_active' => true],
            );

            foreach ($models as $modelName) {
                $model = $brand->phoneModels()->updateOrCreate(
                    ['slug' => Str::slug($modelName)],
                    ['name' => $modelName, 'name_fa' => $this->persianModelName($modelName), 'name_en' => $modelName, 'is_active' => true],
                );

                foreach ($attributes as $attribute) {
                    $model->modelAttributes()->updateOrCreate(
                        ['attribute_id' => $attribute->id],
                        ['is_required' => $attribute->is_required, 'sort_order' => $attribute->sort_order],
                    );
                }
            }
        }
    }

    /** @return array<int, Attribute> */
    private function attributes(): array
    {
        $definitions = [
            ['name' => 'حافظه داخلی', 'slug' => 'storage', 'type' => 'integer', 'unit' => 'GB', 'options' => [32, 64, 128, 256, 512, 1024], 'is_filterable' => true, 'is_required' => true, 'sort_order' => 1],
            ['name' => 'رم', 'slug' => 'ram', 'type' => 'integer', 'unit' => 'GB', 'options' => [2, 3, 4, 6, 8, 12, 16, 24], 'is_filterable' => true, 'is_required' => true, 'sort_order' => 2],
            ['name' => 'رنگ', 'slug' => 'color', 'type' => 'select', 'options' => ['مشکی', 'سفید', 'طلایی', 'نقره‌ای', 'آبی', 'سبز', 'بنفش', 'خاکستری', 'قرمز', 'صورتی', 'نارنجی', 'سرمه‌ای', 'مسی', 'تیفانی', 'کرم', 'زرد'], 'is_filterable' => true, 'is_required' => true, 'sort_order' => 3],
            ['name' => 'وضعیت دستگاه', 'slug' => 'condition', 'type' => 'select', 'options' => ['نو', 'در حد نو', 'کارکرده', 'نیاز به تعمیر'], 'is_filterable' => true, 'is_required' => true, 'sort_order' => 4],
            ['name' => 'سلامت باتری', 'slug' => 'battery_health', 'type' => 'integer', 'unit' => '%', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 5],
            ['name' => 'وضعیت صفحه‌نمایش', 'slug' => 'screen_condition', 'type' => 'select', 'options' => ['سالم', 'خط و خش جزئی', 'خط و خش عمیق', 'شکسته', 'تعویض شده'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 6],
            ['name' => 'وضعیت بدنه', 'slug' => 'body_condition', 'type' => 'select', 'options' => ['سالم', 'خط و خش جزئی', 'خط و خش عمیق', 'ضربه‌خورده', 'تعویض شده'], 'is_filterable' => false, 'is_required' => false, 'sort_order' => 7],
            ['name' => 'آب‌خوردگی', 'slug' => 'water_damage', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 8],
            ['name' => 'تعمیر شده', 'slug' => 'repaired', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 9],
            ['name' => 'توضیح تعمیرات', 'slug' => 'repair_description', 'type' => 'string', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 10],
            ['name' => 'جعبه و لوازم', 'slug' => 'box_and_accessories', 'type' => 'boolean', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 11],
            ['name' => 'شارژر همراه', 'slug' => 'charger_included', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 12],
            ['name' => 'کابل همراه', 'slug' => 'cable_included', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 13],
            ['name' => 'هندزفری همراه', 'slug' => 'headphone_included', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 14],
            ['name' => 'گارانتی', 'slug' => 'warranty', 'type' => 'boolean', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 15],
            ['name' => 'نوع گارانتی', 'slug' => 'warranty_type', 'type' => 'select', 'options' => ['گارانتی شرکتی', 'گارانتی فروشنده', 'گارانتی بین‌المللی', 'بدون گارانتی'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 16],
            ['name' => 'مدت گارانتی باقی‌مانده', 'slug' => 'warranty_remaining_months', 'type' => 'integer', 'unit' => 'ماه', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 17],
            ['name' => 'اورجینال', 'slug' => 'original', 'type' => 'boolean', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 18],
            ['name' => 'رجیستر شده', 'slug' => 'registered', 'type' => 'boolean', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 19],
            ['name' => 'وضعیت رجیستری', 'slug' => 'registration_status', 'type' => 'select', 'options' => ['رجیستر شده به نام خودم', 'رجیستر شده به نام دیگران', 'رجیستر نشده', 'نامشخص'], 'is_filterable' => false, 'is_required' => false, 'sort_order' => 20],
            ['name' => 'فاکتور خرید', 'slug' => 'invoice', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 21],
            ['name' => 'نوع سیم‌کارت', 'slug' => 'sim_type', 'type' => 'select', 'options' => ['تک‌سیم', 'دو سیم'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 22],
            ['name' => 'شبکه', 'slug' => 'network', 'type' => 'select', 'options' => ['2G', '3G', '4G', '5G'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 23],
            ['name' => 'پشتیبانی از eSIM', 'slug' => 'esim', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 24],
            ['name' => 'اندازه صفحه‌نمایش', 'slug' => 'screen_size', 'type' => 'decimal', 'unit' => 'اینچ', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 25],
            ['name' => 'نوع صفحه‌نمایش', 'slug' => 'screen_type', 'type' => 'select', 'options' => ['LCD', 'IPS', 'OLED', 'AMOLED', 'Super AMOLED', 'Retina', 'LTPO AMOLED'], 'is_filterable' => false, 'is_required' => false, 'sort_order' => 26],
            ['name' => 'نرخ تازه‌سازی صفحه', 'slug' => 'refresh_rate', 'type' => 'integer', 'unit' => 'Hz', 'options' => [60, 90, 120, 144, 165], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 27],
            ['name' => 'رزولوشن دوربین اصلی', 'slug' => 'main_camera_mp', 'type' => 'integer', 'unit' => 'MP', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 28],
            ['name' => 'تعداد دوربین', 'slug' => 'camera_count', 'type' => 'integer', 'unit' => 'عدد', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 29],
            ['name' => 'رزولوشن دوربین سلفی', 'slug' => 'selfie_camera_mp', 'type' => 'integer', 'unit' => 'MP', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 30],
            ['name' => 'ظرفیت باتری', 'slug' => 'battery_capacity', 'type' => 'integer', 'unit' => 'mAh', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 31],
            ['name' => 'پشتیبانی از شارژ سریع', 'slug' => 'fast_charging', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 32],
            ['name' => 'توان شارژ', 'slug' => 'charging_power', 'type' => 'integer', 'unit' => 'W', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 33],
            ['name' => 'شارژ بی‌سیم', 'slug' => 'wireless_charging', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 34],
            ['name' => 'مدل پردازنده', 'slug' => 'chipset', 'type' => 'string', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 35],
            ['name' => 'سیستم‌عامل', 'slug' => 'os', 'type' => 'select', 'options' => ['Android', 'iOS', 'HarmonyOS', 'KaiOS', 'سایر'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 36],
            ['name' => 'نسخه سیستم‌عامل', 'slug' => 'os_version', 'type' => 'string', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 37],
            ['name' => 'حسگر اثر انگشت', 'slug' => 'fingerprint', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 38],
            ['name' => 'تشخیص چهره', 'slug' => 'face_recognition', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 39],
            ['name' => 'ضد آب', 'slug' => 'water_resistant', 'type' => 'boolean', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 40],
            ['name' => 'استاندارد ضد آب', 'slug' => 'ip_rating', 'type' => 'select', 'options' => ['IP53', 'IP67', 'IP68', 'IP69', 'بدون استاندارد'], 'is_filterable' => false, 'is_required' => false, 'sort_order' => 41],
            ['name' => 'خروجی هدفون', 'slug' => 'headphone_jack', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 42],
            ['name' => 'پشتیبانی از NFC', 'slug' => 'nfc', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 43],
            ['name' => 'پشتیبانی از مادون قرمز', 'slug' => 'infrared', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 44],
            ['name' => 'شرایط فروش', 'slug' => 'sale_condition', 'type' => 'select', 'options' => ['نقدی', 'اقساطی', 'معاوضه', 'نقدی و اقساطی'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 45],
        ];

        return collect($definitions)->map(function (array $definition): Attribute {
            $attribute = Attribute::query()
                ->where('slug', $definition['slug'])
                ->orWhere('name', $definition['name'])
                ->first();

            if ($attribute) {
                $attribute->update($definition + ['is_active' => true]);

                return $attribute->refresh();
            }

            return Attribute::create($definition + ['is_active' => true]);
        })->all();
    }

    private function persianModelName(string $name): string
    {
        return str_replace(
            ['iPhone', 'Galaxy', 'Redmi Note', 'Redmi', 'POCO', 'Pixel', 'OnePlus', 'Xperia', 'Mate', 'Nova', 'ROG Phone', 'Zenfone', 'Find X', 'Reno', 'Edge', 'Razr', 'Pro Max', 'Pro+', 'Pro', 'Plus', 'Ultra', 'mini', 'Flip', 'Fold'],
            ['آیفون', 'گلکسی', 'ردمی نوت', 'ردمی', 'پوکو', 'پیکسل', 'وان پلاس', 'اکسپریا', 'میت', 'نوا', 'راگ فون', 'زنفون', 'فایند ایکس', 'رینو', 'اج', 'ریزر', 'پرو مکس', 'پرو پلاس', 'پرو', 'پلاس', 'اولترا', 'مینی', 'فلیپ', 'فولد'],
            $name,
        );
    }
}
