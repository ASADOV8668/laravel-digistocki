<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoListingSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::updateOrCreate(
            ['email' => 'demo@digistocki.local'],
            ['name' => 'فروشنده نمونه', 'mobile' => '09120000000', 'password' => Hash::make('password')],
        );

        $listings = [
            ['brand' => 'Apple', 'model' => 'iPhone 13 Pro', 'slug' => 'demo-iphone-13-pro', 'title' => 'آیفون ۱۳ پرو تمیز و سالم', 'price' => 52000000, 'published_at' => now()->subHours(1), 'image' => 'listings/demo-iphone.svg', 'attributes' => ['storage' => 256, 'ram' => 6, 'color' => 'آبی']],
            ['brand' => 'Apple', 'model' => 'iPhone 13 Pro Max', 'slug' => 'demo-iphone-13-pro-max', 'title' => 'آیفون ۱۳ پرو مکس با حافظه ۲۵۶', 'price' => 58500000, 'published_at' => now()->subHours(2), 'image' => 'listings/demo-iphone.svg', 'attributes' => ['storage' => 256, 'ram' => 6, 'color' => 'طلایی']],
            ['brand' => 'Samsung', 'model' => 'Galaxy S23 Ultra', 'slug' => 'demo-galaxy-s23-ultra', 'title' => 'سامسونگ S23 Ultra همراه با جعبه', 'price' => 61000000, 'published_at' => now()->subHours(3), 'image' => 'listings/demo-samsung.svg', 'attributes' => ['storage' => 256, 'ram' => 12, 'color' => 'مشکی']],
            ['brand' => 'Xiaomi', 'model' => 'Redmi Note 13 Pro+', 'slug' => 'demo-redmi-note-13-pro', 'title' => 'ردمی نوت ۱۳ پرو پلاس در حد نو', 'price' => 18500000, 'published_at' => now()->subHours(6), 'image' => 'listings/demo-xiaomi.svg', 'attributes' => ['storage' => 512, 'ram' => 12, 'color' => 'سبز']],
        ];

        foreach ($listings as $data) {
            $brand = Brand::where('name_en', $data['brand'])->firstOrFail();
            $model = $brand->phoneModels()->where('name', $data['model'])->firstOrFail();
            $listing = Listing::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id' => $seller->id,
                    'brand_id' => $brand->id,
                    'phone_model_id' => $model->id,
                    'title' => $data['title'],
                    'description' => 'آگهی نمونه برای نمایش امکانات صفحه اصلی دیجی‌استاکی.',
                    'price' => $data['price'],
                    'is_negotiable' => false,
                    'status' => ListingStatus::Approved,
                    'published_at' => $data['published_at'],
                    'expires_at' => now()->addDays(30),
                ],
            );

            ListingImage::updateOrCreate(
                ['listing_id' => $listing->id, 'sort_order' => 0],
                ['path' => $data['image'], 'is_primary' => true],
            );

            $catalogAttributes = Attribute::query()
                ->whereIn('slug', array_keys($data['attributes']))
                ->get()
                ->keyBy('slug');
            foreach ($data['attributes'] as $slug => $value) {
                $attribute = $catalogAttributes->get($slug);
                if (! $attribute) {
                    continue;
                }
                ListingAttributeValue::updateOrCreate(
                    ['listing_id' => $listing->id, 'attribute_id' => $attribute->id],
                    [
                        'value_integer' => $attribute->type->value === 'integer' ? $value : null,
                        'value_string' => $attribute->type->value === 'select' ? $value : null,
                        'value_decimal' => null,
                        'value_boolean' => null,
                        'value_json' => null,
                    ],
                );
            }
        }
    }
}
