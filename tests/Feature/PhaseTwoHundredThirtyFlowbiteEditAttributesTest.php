<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyFlowbiteEditAttributesTest extends TestCase
{
    public function test_dynamic_edit_attribute_controls_use_shared_flowbite_classes(): void
    {
        $edit = file_get_contents(resource_path('views/listings/edit.blade.php'));

        $this->assertStringContainsString('class="public-check"', $edit);
        $this->assertStringContainsString('class="public-select min-h-28"', $edit);
        $this->assertStringContainsString('class="public-select"', $edit);
        $this->assertStringContainsString('class="public-input"', $edit);
        $this->assertStringNotContainsString('class="w-full rounded-lg border-slate-200 text-sm">', $edit);
    }
}
