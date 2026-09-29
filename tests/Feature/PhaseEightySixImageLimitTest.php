<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseEightySixImageLimitTest extends TestCase
{
    use RefreshDatabase;
    use BuildsListingAttributePayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
    }

    public function test_update_counts_existing_images_when_enforcing_the_eight_image_limit(): void
    {
        $owner = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی محدودیت تصویر',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Rejected,
        ]);
        foreach (range(1, 7) as $sortOrder) {
            ListingImage::create([
                'listing_id' => $listing->id,
                'path' => 'listings/test-'.$sortOrder.'.webp',
                'is_primary' => $sortOrder === 1,
                'sort_order' => $sortOrder,
            ]);
        }

        $this->actingAs($owner)
            ->put(route('listings.update', $listing), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی محدودیت تصویر',
                'price' => 1000000,
                'attributes' => $this->requiredAttributeValues($model),
                'images' => [
                    UploadedFile::fake()->create('one.jpg', 10, 'image/jpeg'),
                    UploadedFile::fake()->create('two.jpg', 10, 'image/jpeg'),
                ],
            ])
            ->assertSessionHasErrors('images');

        $this->assertSame(7, $listing->images()->count());
    }
}
