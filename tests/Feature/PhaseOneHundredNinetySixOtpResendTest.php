<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredNinetySixOtpResendTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_otp_can_be_resent_after_the_first_code(): void
    {
        $this->post(route('register'), ['mobile' => '09124445566'])
            ->assertRedirect(route('register'));

        $this->post(route('register.otp.resend'))
            ->assertRedirect(route('register'))
            ->assertSessionHas('registration_mobile', '09124445566');

        $this->assertDatabaseCount('mobile_otps', 2);
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('otp-resend-form', false)
            ->assertSee('ارسال مجدد کد');
    }

    public function test_login_otp_screen_exposes_a_resend_form(): void
    {
        $user = User::factory()->create(['mobile' => '09123334444']);

        $this->post(route('login'), ['mobile' => $user->mobile])
            ->assertRedirect(route('login'));
        $this->post(route('login.otp.request'), ['mobile' => $user->mobile])
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('otp-resend-form', false)
            ->assertSee(route('login.otp.request'), false)
            ->assertSee('ارسال مجدد کد');
    }
}
