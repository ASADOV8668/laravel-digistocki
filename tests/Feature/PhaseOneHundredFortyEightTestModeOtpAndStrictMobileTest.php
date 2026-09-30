<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\MobileOtp;
use App\Models\User;
use App\Services\ContactOtpService;
use App\Services\MobileOtpService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredFortyEightTestModeOtpAndStrictMobileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_test_mode_uses_the_fixed_code_for_mobile_otp(): void
    {
        $otp = app(MobileOtpService::class)->issue('09301303005', 'login');

        $this->assertNotNull($otp);
        $this->assertTrue(Hash::check('00000', $otp->code_hash));
        $this->assertTrue(app(MobileOtpService::class)->verify('09301303005', 'login', '00000'));
    }

    public function test_test_mode_uses_the_fixed_code_for_contact_otp(): void
    {
        $viewer = User::factory()->create(['mobile' => '09301303006']);
        $seller = User::factory()->create(['mobile' => '09301303007']);
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی OTP تست',
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);

        $otp = app(ContactOtpService::class)->issue($viewer, $listing);

        $this->assertNotNull($otp);
        $this->assertTrue(Hash::check('00000', $otp->code_hash));
    }

    public function test_registration_and_login_reject_international_mobile_formats(): void
    {
        foreach (['+989301303005', '989301303005', '00989301303005'] as $mobile) {
            $this->post(route('login'), ['mobile' => $mobile])->assertSessionHasErrors('mobile');
            $this->post(route('register'), ['mobile' => $mobile])->assertSessionHasErrors('mobile');
        }
    }

    public function test_registration_accepts_only_the_exact_local_eleven_digit_format(): void
    {
        $this->post(route('register'), ['mobile' => '09301303008'])->assertRedirect(route('register'));

        $this->assertDatabaseHas('mobile_otps', [
            'mobile' => '09301303008',
            'purpose' => 'register',
        ]);
        $this->assertDatabaseMissing('mobile_otps', ['mobile' => '+989301303008']);
    }

    public function test_existing_user_can_request_login_otp_with_the_local_format(): void
    {
        $user = User::factory()->create(['mobile' => '09301303009']);

        $this->post(route('login.otp.request'), ['mobile' => $user->mobile])->assertRedirect(route('login'));
        $this->assertDatabaseHas('mobile_otps', [
            'mobile' => $user->mobile,
            'purpose' => 'login',
        ]);
        $this->assertNotNull(MobileOtp::where('mobile', $user->mobile)->where('purpose', 'login')->latest()->first());
    }
}
