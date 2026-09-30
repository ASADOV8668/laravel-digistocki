<?php

namespace Tests\Feature;

use App\Models\SystemOption;
use App\Models\User;
use App\Services\SystemOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredThirtyMobileRegistrationModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_mode_is_mobile_in_system_settings_and_email_registration_is_rejected(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('نحوه ثبت‌نام')
            ->assertSee('فقط با شماره موبایل');

        $this->actingAs($admin)->post(route('logout'));

        $this->post(route('register'), [
            'name' => 'کاربر ایمیلی',
            'email' => 'email-only@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('mobile');

        $this->assertDatabaseMissing('users', ['email' => 'email-only@example.com']);
        $this->assertSame('mobile', app(SystemOptions::class)->registrationMode());
        $this->assertSame('mobile', SystemOption::query()->where('key', 'registration_mode')->value('value') ?? 'mobile');
    }
}
