<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseOneHundredThirtySixAdminListingImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_delete_listing_image_and_primary_is_reassigned(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create(['user_id' => $owner->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'تصویر تست', 'slug' => 'image-test', 'price' => 1000, 'status' => 'pending']);
        $first = ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/first.webp', 'thumbnail_path' => null, 'is_primary' => true, 'sort_order' => 1]);
        $second = ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/second.webp', 'thumbnail_path' => null, 'is_primary' => false, 'sort_order' => 2]);

        $this->actingAs($admin)->delete(route('admin.listings.images.destroy', [$listing, $first]))->assertRedirect();
        $this->assertDatabaseMissing('listing_images', ['id' => $first->id]);
        $this->assertTrue((bool) $second->refresh()->is_primary);
    }
}
