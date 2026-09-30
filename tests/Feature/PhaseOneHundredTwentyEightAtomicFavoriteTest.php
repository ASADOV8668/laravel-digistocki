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

class PhaseOneHundredTwentyEightAtomicFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_listing_cannot_be_favorited(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی علاقه‌مندی منقضی',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->postJson(route('listings.favorite.toggle', $listing))
            ->assertNotFound();

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);
    }
}
