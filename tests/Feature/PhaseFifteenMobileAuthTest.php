<?php

namespace Tests\Feature;

use App\Models\MobileOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseFifteenMobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_mobile_and_local_iranian_format(): void
    {
        $user = User::factory()->create(['mobile' => '09121234567']);

        $this->post(route('login'), [
            'login' => '۰۹۱۲۱۲۳۴۵۶۷',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_empty_password_never_authenticates_a_user_who_has_not_set_a_password(): void
    {
        $user = User::factory()->create([
            'mobile' => '09120009999',
            'password_set_at' => null,
        ]);

        $this->post(route('login'), [
            'mobile' => $user->mobile,
            'password' => '',
        ])->assertRedirect(route('login'));

        $this->assertGuest();

        $this->post(route('login'), [
            'mobile' => $user->mobile,
            'password' => '',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login_with_mobile(): void
    {
        User::factory()->create(['mobile' => '09129876543', 'is_active' => false]);

        $this->post(route('login'), [
            'login' => '09129876543',
            'password' => 'password',
        ])->assertSessionHasErrors('mobile');

        $this->assertGuest();
    }

    public function test_wrong_password_is_shown_on_the_password_step(): void
    {
        User::factory()->create(['mobile' => '09121112233']);

        $this->post(route('login'), ['mobile' => '09121112233'])
            ->assertRedirect(route('login'));

        $this->post(route('login'), [
            'mobile' => '09121112233',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('password');

        $this->get(route('login'))
            ->assertSee('رمز عبور واردشده صحیح نیست.');
    }

    public function test_user_can_register_with_mobile_only(): void
    {
        $this->post(route('register'), ['mobile' => '09121112233'])->assertRedirect(route('register'));
        MobileOtp::latest()->firstOrFail()->update(['code_hash' => Hash::make('123456')]);
        $this->post(route('register.otp.verify'), ['mobile' => '09121112233', 'otp' => '123456'])->assertRedirect(route('register'));
        $this->post(route('register'), ['name' => 'کاربر موبایلی'])->assertRedirect(route('home'));

        $user = User::where('mobile', '09121112233')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_requires_mobile(): void
    {
        $this->post(route('register'), [
            'name' => 'کاربر بدون تماس',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['mobile']);
    }

    public function test_profile_can_update_mobile_with_persian_digits(): void
    {
        $user = User::factory()->create(['mobile' => null]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => '۰۹۱۲۱۲۳۴۵۶۷',
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame('09121234567', $user->refresh()->mobile);
    }

    public function test_registration_otp_accepts_persian_digits(): void
    {
        $this->post(route('register'), ['mobile' => '۰۹۱۲۱۱۱۲۲۳۳'])->assertRedirect(route('register'));
        $otp = MobileOtp::latest()->firstOrFail();
        $otp->update(['code_hash' => Hash::make('123456')]);

        $this->post(route('register.otp.verify'), [
            'mobile' => '۰۹۱۲۱۱۱۲۲۳۳',
            'otp' => '۱۲۳۴۵۶',
        ])->assertRedirect(route('register'));

        $this->post(route('register'), ['name' => 'کاربر رقم فارسی'])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }
}
