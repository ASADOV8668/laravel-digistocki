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
            'Apple' => ['آیفون', ['iPhone 11', 'iPhone 11 Pro', 'iPhone 11 Pro Max', 'iPhone SE 2', 'iPhone 12', 'iPhone 12 mini', 'iPhone 12 Pro', 'iPhone 12 Pro Max', 'iPhone 13', 'iPhone 13 mini', 'iPhone 13 Pro', 'iPhone 13 Pro Max', 'iPhone SE 3', 'iPhone 14', 'iPhone 14 Plus', 'iPhone 14 Pro', 'iPhone 14 Pro Max', 'iPhone 15', 'iPhone 15 Plus', 'iPhone 15 Pro', 'iPhone 15 Pro Max', 'iPhone 16', 'iPhone 16 Plus', 'iPhone 16 Pro', 'iPhone 16 Pro Max']],
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
            ['name' => 'حافظه داخلی', 'type' => 'integer', 'unit' => 'GB', 'is_filterable' => true, 'is_required' => true, 'sort_order' => 1],
            ['name' => 'رم', 'type' => 'integer', 'unit' => 'GB', 'is_filterable' => true, 'is_required' => true, 'sort_order' => 2],
            ['name' => 'رنگ', 'type' => 'select', 'options' => ['مشکی', 'سفید', 'طلایی', 'آبی', 'سبز', 'بنفش', 'خاکستری'], 'is_filterable' => true, 'is_required' => true, 'sort_order' => 3],
            ['name' => 'سلامت باتری', 'type' => 'integer', 'unit' => '%', 'is_filterable' => true, 'is_required' => false, 'sort_order' => 4],
            ['name' => 'وضعیت دستگاه', 'type' => 'select', 'options' => ['نو', 'در حد نو', 'کارکرده', 'نیاز به تعمیر'], 'is_filterable' => true, 'is_required' => true, 'sort_order' => 5],
            ['name' => 'جعبه و لوازم', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 6],
            ['name' => 'گارانتی', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 7],
            ['name' => 'مدت گارانتی باقی‌مانده', 'type' => 'integer', 'unit' => 'ماه', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 8],
            ['name' => 'اورجینال', 'type' => 'boolean', 'is_filterable' => false, 'is_required' => false, 'sort_order' => 9],
            ['name' => 'نوع سیم‌کارت', 'type' => 'select', 'options' => ['تک‌سیم', 'دو سیم'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 10],
            ['name' => 'شبکه', 'type' => 'select', 'options' => ['4G', '5G'], 'is_filterable' => true, 'is_required' => false, 'sort_order' => 11],
        ];

        return collect($definitions)->map(fn (array $definition) => Attribute::updateOrCreate(
            ['name' => $definition['name']],
            $definition + ['is_active' => true],
        ))->all();
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
