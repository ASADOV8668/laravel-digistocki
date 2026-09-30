<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredNinetyFiveOtpAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_countdown_is_announced_and_password_errors_use_the_password_field(): void
    {
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $register = file_get_contents(resource_path('views/auth/register.blade.php'));

        $this->assertNotFalse($login);
        $this->assertNotFalse($register);
        foreach ([$login, $register] as $view) {
            $this->assertStringContainsString('role="timer"', $view);
            $this->assertStringContainsString('aria-live="polite"', $view);
            $this->assertStringContainsString('aria-atomic="true"', $view);
        }
        $this->assertStringContainsString(':messages="$errors->get(\'password\')" class="mt-2"', $login);
    }
}
