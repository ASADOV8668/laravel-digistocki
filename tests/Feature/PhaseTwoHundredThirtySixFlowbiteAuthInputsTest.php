<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredThirtySixFlowbiteAuthInputsTest extends TestCase
{
    public function test_shared_auth_inputs_use_the_flowbite_public_input_contract(): void
    {
        $component = File::get(resource_path('views/components/text-input.blade.php'));
        $label = File::get(resource_path('views/components/input-label.blade.php'));

        $this->assertStringContainsString("'class' => 'public-input'", $component);
        $this->assertStringContainsString('text-neutral', $label);
        $this->assertStringNotContainsString('focus:ring-primary', $component);
    }

    public function test_legacy_guest_auth_views_use_flowbite_spacing_and_slate_text(): void
    {
        foreach (['forgot-password', 'confirm-password', 'reset-password', 'verify-email'] as $view) {
            $source = File::get(resource_path("views/auth/{$view}.blade.php"));

            $this->assertStringNotContainsString('text-gray-', $source);
            $this->assertStringNotContainsString('block mt-1 w-full', $source);
        }
    }
}
