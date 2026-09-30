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

class PhaseOneHundredTwentyAdminExpiredListingFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reach_the_expired_listing_filter_from_status_cards(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی منقضی‌شده تست',
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => ListingStatus::Expired,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.listings.index'))
            ->assertOk()
            ->assertSee('منقضی‌شده')
            ->assertSee('status=expired', false);

        $this->actingAs($admin)
            ->get(route('admin.listings.index', ['status' => 'expired']))
            ->assertOk()
            ->assertSee($listing->title);
    }
}
