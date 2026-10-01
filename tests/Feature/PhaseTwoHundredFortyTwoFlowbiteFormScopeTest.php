<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredFortyTwoFlowbiteFormScopeTest extends TestCase
{
    public function test_all_public_textual_form_controls_have_the_flowbite_scope(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('.mobile-shell :where(input[type=', $css);
        $this->assertStringContainsString('focus:ring-4 focus:ring-primary/20', $css);
    }

    public function test_form_scope_does_not_leak_into_admin_styles(): void
    {
        $admin = File::get(resource_path('css/admin.css'));

        $this->assertStringNotContainsString('.mobile-shell :where(input[type=', $admin);
    }
}
