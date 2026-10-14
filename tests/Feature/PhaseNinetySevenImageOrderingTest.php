<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseNinetySevenImageOrderingTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
    }

    public function test_uploaded_images_keep_order_and_only_first_image_is_primary(): void
    {
        $user = User::factory()->create();
        $this->storeApplication($user);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی ترتیب تصاویر',
            'price' => 21000000,
            'attributes' => $this->requiredAttributeValues($model),
            'images' => [
                UploadedFile::fake()->create('first.jpg', 20, 'image/jpeg'),
                UploadedFile::fake()->create('second.jpg', 20, 'image/jpeg'),
            ],
        ])->assertRedirect();

        $listing = Listing::where('title', 'آگهی ترتیب تصاویر')->firstOrFail();
        $images = $listing->images()->get();

        $this->assertCount(2, $images);
        $this->assertTrue((bool) $images[0]->is_primary);
        $this->assertFalse((bool) $images[1]->is_primary);
        $this->assertSame([1, 2], $images->pluck('sort_order')->all());
    }

    public function test_selected_primary_image_index_is_persisted(): void
    {
        $user = User::factory()->create();
        $this->storeApplication($user);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تصویر اصلی انتخابی',
            'price' => 22000000,
            'primary_image_index' => 1,
            'attributes' => $this->requiredAttributeValues($model),
            'images' => [
                UploadedFile::fake()->create('first.jpg', 20, 'image/jpeg'),
                UploadedFile::fake()->create('selected.jpg', 20, 'image/jpeg'),
            ],
        ])->assertRedirect();

        $images = Listing::where('title', 'آگهی تصویر اصلی انتخابی')->firstOrFail()->images()->get();

        $this->assertFalse((bool) $images[0]->is_primary);
        $this->assertTrue((bool) $images[1]->is_primary);
    }

    public function test_editing_without_new_images_keeps_the_existing_primary_image(): void
    {
        $user = User::factory()->create();
        $this->storeApplication($user);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی حفظ تصویر شاخص',
            'slug' => Str::uuid(),
            'price' => 22000000,
            'status' => \App\Enums\ListingStatus::Pending,
        ]);
        $primary = ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/'.$listing->id.'/primary.webp',
            'thumbnail_path' => 'listings/'.$listing->id.'/primary-thumb.webp',
            'is_primary' => true,
            'sort_order' => 1,
        ]);
        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/'.$listing->id.'/second.webp',
            'thumbnail_path' => 'listings/'.$listing->id.'/second-thumb.webp',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($user)->put(route('listings.update', $listing), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی حفظ تصویر شاخص ویرایش‌شده',
            'price' => 23000000,
            'attributes' => $this->requiredAttributeValues($model),
            'primary_image_index' => '0',
            'primary_image_id' => '',
        ])->assertRedirect();

        $this->assertTrue((bool) $primary->refresh()->is_primary);
        $this->assertSame(1, $listing->images()->where('is_primary', true)->count());
    }
}
