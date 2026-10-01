<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyFiveMobileHelpTextTest extends TestCase
{
    public function test_public_mobile_help_text_uses_generic_example_number(): void
    {
        $login = file_get_contents(resource_path('views/auth/login.blade.php'));
        $register = file_get_contents(resource_path('views/auth/register.blade.php'));

        foreach ([$login, $register] as $view) {
            $this->assertStringContainsString('09120000000', $view);
            $this->assertStringNotContainsString('09301303005', $view);
        }
    }
}
