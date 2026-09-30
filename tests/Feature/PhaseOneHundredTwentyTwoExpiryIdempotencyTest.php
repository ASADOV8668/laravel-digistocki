<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use App\Services\ListingLifecycleService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredTwentyTwoExpiryIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeating_expiry_run_does_not_duplicate_transition_or_notification(): void
    {
        Notification::fake();
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی انقضای idempotent',
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => ListingStatus::Approved,
            'expires_at' => now()->subMinute(),
        ]);

        $lifecycle = app(ListingLifecycleService::class);

        $this->assertSame(1, $lifecycle->expireDueListings());
        $this->assertSame(0, $lifecycle->expireDueListings());
        $this->assertSame(ListingStatus::Expired, $listing->refresh()->status);
        Notification::assertSentTo($user, ListingStatusNotification::class, 1);
    }
}
