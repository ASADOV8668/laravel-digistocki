<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\ContactOtp;
use App\Models\Listing;
use App\Models\User;
use App\Services\ContactOtpService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredTwentyNineAtomicOtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_attempt_limit_is_enforced_without_reusing_a_consumed_attempt(): void
    {
        $this->seed(CatalogSeeder::class);
        $viewer = User::factory()->create();
        $listing = $this->listing(User::factory()->create());
        $otp = ContactOtp::create([
            'user_id' => $viewer->id,
            'listing_id' => $listing->id,
            'code_hash' => Hash::make('123456'),
            'attempts' => ContactOtpService::MAX_ATTEMPTS - 1,
            'expires_at' => now()->addMinutes(5),
        ]);

        $service = app(ContactOtpService::class);
        $this->assertFalse($service->verify($viewer, $listing, '000000'));
        $this->assertSame(ContactOtpService::MAX_ATTEMPTS, $otp->refresh()->attempts);
        $this->assertFalse($service->verify($viewer, $listing, '123456'));
        $this->assertNull($otp->refresh()->verified_at);
    }

    private function listing(User $seller): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی OTP اتمیک',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);
    }
}
