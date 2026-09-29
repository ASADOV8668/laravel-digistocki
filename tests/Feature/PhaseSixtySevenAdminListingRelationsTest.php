<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSixtySevenAdminListingRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_listing_feed_eager_loads_all_template_relations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی مدیریت eager load',
            'slug' => Str::uuid(),
            'price' => 12000000,
            'status' => ListingStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.listings.index'));

        $response->assertOk();
        $feedListing = $response->viewData('listings')->getCollection()->firstWhere('id', $listing->id);
        $this->assertNotNull($feedListing);
        $this->assertTrue($feedListing->relationLoaded('user'));
        $this->assertTrue($feedListing->relationLoaded('brand'));
        $this->assertTrue($feedListing->relationLoaded('phoneModel'));
        $this->assertTrue($feedListing->relationLoaded('primaryImage'));
    }
}
