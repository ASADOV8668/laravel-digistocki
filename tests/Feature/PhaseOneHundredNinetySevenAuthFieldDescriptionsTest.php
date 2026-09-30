<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredNinetySevenAuthFieldDescriptionsTest extends TestCase
{
    public function test_login_fields_reference_their_help_and_error_descriptions(): void
    {
        $view = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertStringContainsString('id="mobile-help"', $view);
        $this->assertStringContainsString('id="mobile-error"', $view);
        $this->assertStringContainsString('aria-describedby="mobile-help mobile-error"', $view);
        $this->assertStringContainsString('aria-describedby="password-error"', $view);
        $this->assertStringContainsString('aria-describedby="otp-expiry otp-error"', $view);
    }

    public function test_registration_fields_reference_their_help_and_error_descriptions(): void
    {
        $view = file_get_contents(resource_path('views/auth/register.blade.php'));

        $this->assertStringContainsString('aria-describedby="mobile-help mobile-error"', $view);
        $this->assertStringContainsString('aria-describedby="otp-expiry otp-error"', $view);
        $this->assertStringContainsString('aria-describedby="name-error"', $view);
        $this->assertStringContainsString('aria-describedby="national-id-error"', $view);
        $this->assertStringContainsString('id="national-id-error"', $view);
    }
}
