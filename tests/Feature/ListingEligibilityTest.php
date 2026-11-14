<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use App\Services\ListingRules;
use Carbon\Carbon;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class ListingEligibilityTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    public function test_future_license_keeps_listing_creation_available_and_expired_license_blocks_it(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $store = $this->storeApplication($user);

        $store->update(['business_license_expires_at' => Carbon::tomorrow()]);
        $this->assertTrue(app(ListingRules::class)->canCreate($user->refresh()));

        $store->update(['business_license_expires_at' => Carbon::yesterday()]);
        $this->assertFalse(app(ListingRules::class)->canCreate($user->refresh()));
    }

    public function test_listing_submission_redirects_with_feedback_instead_of_returning_a_403(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $store = $this->storeApplication($user);
        $store->update(['business_license_expires_at' => Carbon::yesterday()]);

        $this->actingAs($user)
            ->post(route('listings.store'), [])
            ->assertRedirect(route('home'))
            ->assertSessionHas('status');
    }

    public function test_admin_disabled_store_cannot_submit_a_listing(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $store = $this->storeApplication($user);
        $store->update(['is_admin_disabled' => true]);

        $this->assertFalse(app(ListingRules::class)->canCreate($user->refresh()));
    }
}
