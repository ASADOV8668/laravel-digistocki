<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyFourFlowbiteGuestActionsTest extends TestCase
{
    public function test_guest_auth_actions_use_shared_flowbite_button_components(): void
    {
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $register = file_get_contents(resource_path('views/auth/register.blade.php'));
        $verify = file_get_contents(resource_path('views/auth/verify-email.blade.php'));

        $this->assertGreaterThanOrEqual(3, substr_count($login, '<x-primary-button class="w-full">'));
        $this->assertGreaterThanOrEqual(3, substr_count($register, '<x-primary-button class="w-full">'));
        $this->assertStringContainsString('<x-secondary-button type="submit"', $verify);
        $this->assertStringNotContainsString('focus:ring-indigo-500', $verify);
    }
}
