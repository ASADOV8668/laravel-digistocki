<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\SellerStore;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredSeventyOneListingStorefrontLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_listing_cards_link_to_an_enabled_seller_storefront(): void
    {
        $seller = User::factory()->create();
        $store = SellerStore::create(['user_id' => $seller->id, 'slug' => 'card-store', 'name' => 'غرفه کارت', 'is_enabled' => true]);
        $listing = $this->listing($seller, 'آگهی با غرفه');

        $response = $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('غرفه فروشنده')
            ->assertSee(route('storefront.show', $store), false);

        $feedListing = $response->viewData('listings')->getCollection()->firstWhere('id', $listing->id);
        $this->assertNotNull($feedListing);
        $this->assertTrue($feedListing->relationLoaded('user'));
        $this->assertTrue($feedListing->user->relationLoaded('storefront'));
    }

    public function test_listing_cards_hide_storefront_link_when_store_is_disabled(): void
    {
        $seller = User::factory()->create();
        SellerStore::create(['user_id' => $seller->id, 'slug' => 'hidden-card-store', 'name' => 'غرفه مخفی کارت', 'is_enabled' => false]);
        $this->listing($seller, 'آگهی بدون غرفه عمومی');

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertDontSee('غرفه فروشنده');
    }

    private function listing(User $seller, string $title): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
