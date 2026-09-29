<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFifteenMobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_mobile_and_iranian_international_prefix(): void
    {
        $user = User::factory()->create(['mobile' => '09121234567']);

        $this->post(route('login'), [
            'login' => '+98 912 123 4567',
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
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_can_register_with_mobile_only(): void
    {
        $this->post(route('register'), [
            'name' => 'کاربر موبایلی',
            'mobile' => '+98 912 111 2233',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('mobile', '09121112233')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_requires_email_or_mobile(): void
    {
        $this->post(route('register'), [
            'name' => 'کاربر بدون تماس',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['email', 'mobile']);
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
