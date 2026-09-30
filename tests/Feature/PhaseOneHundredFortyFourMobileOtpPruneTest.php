<?php

namespace Tests\Feature;

use App\Models\MobileOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseOneHundredFortyFourMobileOtpPruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_otp_prune_removes_old_expired_and_verified_records(): void
    {
        $expired = MobileOtp::create(['mobile' => '09120000001', 'purpose' => 'login', 'code_hash' => Hash::make('123456'), 'expires_at' => now()->subDay()]);
        $verified = MobileOtp::create(['mobile' => '09120000002', 'purpose' => 'register', 'code_hash' => Hash::make('123456'), 'expires_at' => now()->addHour(), 'verified_at' => now()->subDay()]);
        $fresh = MobileOtp::create(['mobile' => '09120000003', 'purpose' => 'login', 'code_hash' => Hash::make('123456'), 'expires_at' => now()->addHour()]);

        $this->assertSame(0, Artisan::call('mobile-otps:prune', ['--hours' => 24]));
        $this->assertDatabaseMissing('mobile_otps', ['id' => $expired->id]);
        $this->assertDatabaseMissing('mobile_otps', ['id' => $verified->id]);
        $this->assertDatabaseHas('mobile_otps', ['id' => $fresh->id]);
    }
}
