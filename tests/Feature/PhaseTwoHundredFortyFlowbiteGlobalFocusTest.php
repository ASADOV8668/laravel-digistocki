<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredFortyFlowbiteGlobalFocusTest extends TestCase
{
    public function test_public_stylesheet_has_a_global_flowbite_focus_visible_fallback(): void
    {
        $source = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString(':where(a, button, input, select, textarea):focus-visible', $source);
        $this->assertStringContainsString('ring-4 ring-primary/20 ring-offset-2', $source);
    }

    public function test_focus_fallback_is_scoped_to_the_public_stylesheet(): void
    {
        $adminSource = File::get(resource_path('css/admin.css'));

        $this->assertStringNotContainsString(':where(a, button, input, select, textarea):focus-visible', $adminSource);
    }
}
