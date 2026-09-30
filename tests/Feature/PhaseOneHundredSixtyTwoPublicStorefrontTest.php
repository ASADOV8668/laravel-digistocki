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

class PhaseOneHundredSixtyTwoPublicStorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_enabled_storefront_displays_owner_details_and_published_listings(): void
    {
        $seller = User::factory()->create(['name' => 'فروشنده غرفه', 'mobile' => '09120002222']);
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'mobile-shop',
            'name' => 'فروشگاه موبایل',
            'is_enabled' => true,
            'contact_phone' => '09123334444',
        ]);
        $listing = $this->listing($seller, 'آگهی داخل غرفه');

        $this->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee('فروشگاه موبایل')
            ->assertSee('09123334444')
            ->assertSee($listing->title)
            ->assertSee(route('listings.show', $listing), false);
    }

    public function test_disabled_storefront_shows_a_public_disabled_message(): void
    {
        $store = SellerStore::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'hidden-shop',
            'name' => 'غرفه خاموش',
            'is_enabled' => false,
        ]);

        $this->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee('غرفه این فروشنده غیرفعال است')
            ->assertSee('disabled-storefront-icon', false);
    }

    public function test_listing_page_links_to_an_enabled_seller_storefront(): void
    {
        $seller = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'seller-booth',
            'name' => 'غرفه فروشنده',
            'is_enabled' => true,
        ]);
        $listing = $this->listing($seller, 'آگهی با غرفه');

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee('مشاهده غرفه')
            ->assertSee(route('storefront.show', $store), false);
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
            'price' => 18000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
