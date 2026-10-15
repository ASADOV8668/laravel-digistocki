<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_hides_current_password_before_the_user_sets_a_password(): void
    {
        $user = User::factory()->create(['password_set_at' => null]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('شما هنوز رمز عبوری انتخاب نکرده‌اید؛ رمز عبور جدید خود را وارد کنید.')
            ->assertDontSee('name="current_password"', false);
    }

    public function test_profile_shows_current_password_after_the_user_sets_a_password(): void
    {
        $user = User::factory()->create(['password_set_at' => now()]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('name="current_password"', false)
            ->assertDontSee('شما هنوز رمز عبوری انتخاب نکرده‌اید', false);
    }

    public function test_password_update_success_is_announced_with_a_flowbite_toast(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['status' => 'password-updated'])
            ->get('/profile')
            ->assertOk()
            ->assertSee('رمز عبور تغییر کرد')
            ->assertSee('رمز عبور شما با موفقیت ذخیره شد.')
            ->assertSee('x-init="show(', false)
            ->assertDontSee('show-notification.window', false);
    }

    public function test_registration_session_flag_hides_current_password_even_if_legacy_timestamp_exists(): void
    {
        $user = User::factory()->create(['password_set_at' => now()]);

        $this->actingAs($user)
            ->withSession(['password_setup_available' => true])
            ->get('/profile')
            ->assertOk()
            ->assertSee('شما هنوز رمز عبوری انتخاب نکرده‌اید؛ رمز عبور جدید خود را وارد کنید.')
            ->assertDontSee('name="current_password"', false);
    }

    public function test_profile_password_requires_uppercase_lowercase_and_number(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'passwordonly',
                'password_confirmation' => 'passwordonly',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirect('/profile');
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('NewPassword1', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');

        $this->assertSame(
            'رمز عبور فعلی صحیح نیست.',
            $response->getSession()->get('errors')->getBag('updatePassword')->first('current_password'),
        );
    }

    public function test_first_password_setup_uses_the_session_flag_without_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->withSession(['password_setup_available' => true])
            ->from('/profile')
            ->put('/password', [
                'password' => 'NewPassword1',
                'password_confirmation' => 'NewPassword1',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('NewPassword1', $user->refresh()->password));
        $this->assertFalse(session()->has('password_setup_available'));
    }
}
