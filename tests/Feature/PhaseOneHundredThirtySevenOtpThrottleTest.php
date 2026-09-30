<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PhaseOneHundredThirtySevenOtpThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
    }

    public function test_login_otp_requests_are_rate_limited(): void
    {
        $user = User::factory()->create(['mobile' => '09121234567']);
        $this->post(route('login'), ['mobile' => $user->mobile])->assertRedirect();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.otp.request'), ['mobile' => $user->mobile])->assertRedirect();
        }

        $this->post(route('login.otp.request'), ['mobile' => $user->mobile])->assertStatus(429);
    }
}
