<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\SystemOption;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSixtyTwoOtpDeliveryFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_failed_live_sms_does_not_create_an_unusable_otp(): void
    {
        Log::shouldReceive('warning')->once()->with('SMS live mode selected, but provider is not configured yet', \Mockery::type('array'));
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);
        $viewer = User::factory()->create(['mobile' => '09121111112']);
        $listing = $this->listing(User::factory()->create(['mobile' => '09129999999']));

        $this->actingAs($viewer)
            ->post(route('listings.contact-otp', $listing))
            ->assertRedirect()
            ->assertSessionHasErrors('contact_otp');

        $this->assertDatabaseCount('contact_otps', 0);
        $this->assertFalse(session()->has('contact_otp_listing_id'));
    }

    private function listing(User $seller): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی خطای ارسال OTP',
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
