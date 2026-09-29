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

class PhaseSixtyThreeListingFeedRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_listing_feed_eager_loads_both_primary_and_fallback_images(): void
    {
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی feed بدون N plus 1',
            'slug' => Str::uuid(),
            'price' => 12000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);

        $response = $this->get(route('listings.index'));

        $response->assertOk();
        $feedListing = $response->viewData('listings')->getCollection()->firstWhere('id', $listing->id);
        $this->assertNotNull($feedListing);
        $this->assertTrue($feedListing->relationLoaded('primaryImage'));
        $this->assertTrue($feedListing->relationLoaded('images'));
    }
}
