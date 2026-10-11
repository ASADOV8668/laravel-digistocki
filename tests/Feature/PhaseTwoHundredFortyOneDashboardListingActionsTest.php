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
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseTwoHundredFortyOneDashboardListingActionsTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_owner_can_quick_update_price_and_disable_listing(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'آگهی سریع', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);

        $this->actingAs($user)->patch(route('listings.quick-update', $listing), ['price' => 2500000])->assertRedirect();
        $this->actingAs($user)->patch(route('listings.toggle-disabled', $listing))->assertRedirect();
        $this->assertSame(2500000, $listing->refresh()->price);
        $this->assertTrue($listing->is_user_disabled);
    }

    public function test_quick_update_requires_a_price_when_contact_mode_is_not_selected(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'قیمت الزامی', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);

        $this->actingAs($user)
            ->patch(route('listings.quick-update', $listing), ['price' => ''])
            ->assertSessionHasErrors('price');

        $this->assertSame(1000000, $listing->refresh()->price);
        $this->assertFalse($listing->price_on_request);
    }

    public function test_dashboard_clone_page_is_prefilled_from_the_owner_listing(): void
    {
        $user = User::factory()->create();
        SellerStore::create(['user_id' => $user->id, 'slug' => 'clone-store-'.$user->id, 'name' => 'فروشگاه کپی']);
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'آگهی قابل کپی', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);

        $this->actingAs($user)->get(route('listings.create', ['clone' => $listing->id]))
            ->assertOk()
            ->assertSee('آگهی قابل کپی (کپی)')
            ->assertSee('name="clone"', false);
    }
}
