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
            'login' => '09121234567',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
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
}
