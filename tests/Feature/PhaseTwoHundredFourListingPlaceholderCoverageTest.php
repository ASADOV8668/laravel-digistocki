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

class PhaseTwoHundredFourListingPlaceholderCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_autocomplete_returns_the_shared_placeholder_for_image_less_listings(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی بدون تصویر برای autocomplete',
            'slug' => Str::uuid(),
            'price' => 25000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $this->getJson(route('listings.autocomplete', ['q' => 'autocomplete']))
            ->assertOk()
            ->assertJsonPath('0.image', asset('images/listing-placeholder.svg'));
    }

    public function test_listing_card_keeps_the_shared_placeholder_asset(): void
    {
        $this->assertStringContainsString(
            'listing-placeholder.svg',
            file_get_contents(resource_path('views/components/listing-card.blade.php'))
        );
    }
}
