<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredThirtyNineFlowbiteButtonContractTest extends TestCase
{
    public function test_shared_buttons_use_defined_flowbite_focus_colors(): void
    {
        $primary = File::get(resource_path('views/components/primary-button.blade.php'));
        $secondary = File::get(resource_path('views/components/secondary-button.blade.php'));

        $this->assertStringContainsString('focus:ring-primary/30', $primary);
        $this->assertStringContainsString('focus:ring-offset-2', $primary);
        $this->assertStringNotContainsString('focus:ring-primary-200', $primary);
        $this->assertStringContainsString('focus:ring-offset-2', $secondary);
    }

    public function test_public_toast_close_button_is_keyboard_visible(): void
    {
        $layout = File::get(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('data-dismiss-target="#public-toast"', $layout);
        $this->assertStringContainsString('focus:outline-none focus:ring-4 focus:ring-primary/20', $layout);
    }
}
