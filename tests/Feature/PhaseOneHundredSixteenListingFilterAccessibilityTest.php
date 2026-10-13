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

class PhaseOneHundredSixteenListingFilterAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        foreach ([1000000, 2000000] as $index => $price) {
            Listing::create([
                'user_id' => User::factory()->create()->id,
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی دسترسی '.$index,
                'slug' => Str::uuid(),
                'price' => $price,
                'status' => ListingStatus::Approved,
                'published_at' => now()->subMinutes($index),
            ]);
        }
    }

    public function test_listing_filters_have_accessible_names_and_mobile_relationships(): void
    {
        $response = $this->get(route('listings.index'));

        $response->assertOk();
        $response->assertSee('aria-controls="listing-filters"', false);
        $response->assertSee(':aria-expanded="filtersOpen.toString()"', false);
        $response->assertSee('id="listing-filters"', false);
        $response->assertSee('for="listing-search"', false);
        $response->assertSee('for="listing-min-price"', false);
        $response->assertSee('for="listing-max-price"', false);
    }
}
