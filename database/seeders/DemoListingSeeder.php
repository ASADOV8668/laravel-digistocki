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
use Illuminate\Support\Facades\Storage;
use Sadegh19b\LaravelIranCities\Models\Province;

class DemoListingSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::updateOrCreate(
            ['email' => 'demo@digistocki.local'],
            ['name' => 'فروشنده نمونه', 'mobile' => '09120000000', 'password' => Hash::make('password')],
        );

        $listings = [
            ['brand' => 'Apple', 'model' => 'iPhone 13 Pro', 'slug' => 'demo-iphone-13-pro', 'title' => 'آیفون ۱۳ پرو تمیز و سالم', 'price' => 52000000, 'province' => 'تهران', 'city' => 'تهران', 'published_at' => now()->subHours(1), 'images' => ['listings/demo-iphone-front.svg', 'listings/demo-iphone-back.svg', 'listings/demo-iphone-side.svg'], 'attributes' => ['storage' => 256, 'ram' => 6, 'color' => 'آبی']],
            ['brand' => 'Apple', 'model' => 'iPhone 13 Pro Max', 'slug' => 'demo-iphone-13-pro-max', 'title' => 'آیفون ۱۳ پرو مکس با حافظه ۲۵۶', 'price' => 58500000, 'province' => 'اصفهان', 'city' => 'اصفهان', 'published_at' => now()->subHours(2), 'image' => 'listings/demo-iphone.svg', 'attributes' => ['storage' => 256, 'ram' => 6, 'color' => 'طلایی']],
            ['brand' => 'Samsung', 'model' => 'Galaxy S23 Ultra', 'slug' => 'demo-galaxy-s23-ultra', 'title' => 'سامسونگ S23 Ultra همراه با جعبه', 'price' => 61000000, 'province' => 'مازندران', 'city' => 'ساری', 'published_at' => now()->subHours(3), 'image' => 'listings/demo-samsung.svg', 'attributes' => ['storage' => 256, 'ram' => 12, 'color' => 'مشکی']],
            ['brand' => 'Xiaomi', 'model' => 'Redmi Note 13 Pro+', 'slug' => 'demo-redmi-note-13-pro', 'title' => 'ردمی نوت ۱۳ پرو پلاس در حد نو', 'price' => 18500000, 'province' => 'فارس', 'city' => 'شیراز', 'published_at' => now()->subHours(6), 'image' => 'listings/demo-xiaomi.svg', 'attributes' => ['storage' => 512, 'ram' => 12, 'color' => 'سبز']],
        ];

        foreach ($listings as $data) {
            $brand = Brand::where('name_en', $data['brand'])->firstOrFail();
            $model = $brand->phoneModels()->where('name', $data['model'])->firstOrFail();
            $province = Province::query()->where('name', $data['province'])->first();
            $city = $province?->cities()->where('name', $data['city'])->first();
            $listing = Listing::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id' => $seller->id,
                    'brand_id' => $brand->id,
                    'phone_model_id' => $model->id,
                    'province_id' => $province?->id,
                    'city_id' => $city?->id,
                    'title' => $data['title'],
                    'description' => 'آگهی نمونه برای نمایش امکانات صفحه اصلی دیجی‌استاکی.',
                    'price' => $data['price'],
                    'is_negotiable' => false,
                    'status' => ListingStatus::Approved,
                    'published_at' => $data['published_at'],
                    'expires_at' => now()->addDays(30),
                ],
            );

            if (isset($data['images'])) {
                ListingImage::query()->where('listing_id', $listing->id)->delete();

                foreach ($data['images'] as $sortOrder => $imagePath) {
                    $this->ensureDemoImage($imagePath);
                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'path' => $imagePath,
                        'is_primary' => $sortOrder === 0,
                        'sort_order' => $sortOrder,
                    ]);
                }
            } else {
                ListingImage::updateOrCreate(
                    ['listing_id' => $listing->id, 'sort_order' => 0],
                    ['path' => $data['image'], 'is_primary' => true],
                );
            }

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

    private function ensureDemoImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            return;
        }

        $sourcePath = public_path('images/'.$path);
        $contents = is_file($sourcePath) ? file_get_contents($sourcePath) : false;

        if ($contents !== false) {
            Storage::disk('public')->put($path, $contents);
        }
    }
}
