<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyNineFlowbitePublicInputTest extends TestCase
{
    public function test_public_input_and_resend_controls_use_shared_flowbite_styles(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $home = file_get_contents(resource_path('views/welcome.blade.php'));
        $listing = file_get_contents(resource_path('views/listings/show.blade.php'));
        $storefront = file_get_contents(resource_path('views/storefront/show.blade.php'));
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $register = file_get_contents(resource_path('views/auth/register.blade.php'));

        $this->assertStringContainsString('.public-input', $css);
        $this->assertStringContainsString('class="public-input bg-white', $home);
        $this->assertStringContainsString('class="public-input min-w-0', $listing);
        $this->assertStringContainsString('class="public-input min-w-0', $storefront);
        $this->assertGreaterThanOrEqual(2, substr_count($login, 'mobile-button w-full'));
        $this->assertStringContainsString('mobile-button w-full border border-primary/20', $register);
    }
}
