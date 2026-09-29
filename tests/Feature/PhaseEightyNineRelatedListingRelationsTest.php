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

class PhaseEightyNineRelatedListingRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_related_listing_feed_eager_loads_attribute_values_used_for_scoring(): void
    {
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $main = $this->listing('آگهی اصلی', $brand->id, $model->id);
        $related = $this->listing('آگهی مرتبط', $brand->id, $model->id);

        $this->get(route('listings.show', $main))
            ->assertOk()
            ->assertViewHas('relatedListings', fn ($listings) => $listings->contains('id', $related->id)
                && $listings->firstWhere('id', $related->id)->relationLoaded('attributeValues'));
    }

    private function listing(string $title, int $brandId, int $modelId): Listing
    {
        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
