<?php

namespace Tests\Feature;

use App\Models\ContactOtp;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use App\Enums\ListingStatus;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSixtyEightContactOtpPruneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_prune_command_removes_old_expired_and_consumed_otps_only(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی تست پاکسازی OTP',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);
        $old = Carbon::now()->subDays(2);

        ContactOtp::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'code_hash' => Hash::make('111111'), 'expires_at' => $old]);
        ContactOtp::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'code_hash' => Hash::make('222222'), 'expires_at' => Carbon::now()->addHour(), 'verified_at' => $old]);
        $recentExpired = ContactOtp::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'code_hash' => Hash::make('333333'), 'expires_at' => Carbon::now()->subHour()]);
        $active = ContactOtp::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'code_hash' => Hash::make('444444'), 'expires_at' => Carbon::now()->addHour()]);

        $this->assertSame(0, Artisan::call('contact-otps:prune'));
        $this->assertDatabaseCount('contact_otps', 2);
        $this->assertDatabaseHas('contact_otps', ['id' => $recentExpired->id]);
        $this->assertDatabaseHas('contact_otps', ['id' => $active->id]);
    }
}
