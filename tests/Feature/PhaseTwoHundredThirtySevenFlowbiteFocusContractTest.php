<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredThirtySevenFlowbiteFocusContractTest extends TestCase
{
    public function test_public_flowbite_primitives_have_consistent_focus_rings(): void
    {
        $source = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('focus:ring-4 focus:ring-primary/20', $source);
        $this->assertStringContainsString('focus:ring-offset-2', $source);
        $this->assertStringContainsString('focus:outline-none focus:ring-4', $source);
    }

    public function test_admin_styles_are_not_part_of_the_public_focus_contract(): void
    {
        $adminSource = File::get(resource_path('css/admin.css'));

        $this->assertStringNotContainsString('.mobile-button', $adminSource);
        $this->assertStringNotContainsString('.public-input', $adminSource);
    }
}
