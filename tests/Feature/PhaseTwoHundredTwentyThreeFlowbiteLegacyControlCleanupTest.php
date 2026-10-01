<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyThreeFlowbiteLegacyControlCleanupTest extends TestCase
{
    public function test_remaining_public_inputs_use_flowbite_control_states(): void
    {
        $create = file_get_contents(resource_path('views/listings/create.blade.php'));
        $storefront = file_get_contents(resource_path('views/storefront/edit.blade.php'));

        $this->assertStringContainsString('focus:ring-2 focus:ring-primary/30', $create);
        $this->assertStringContainsString('class="public-check h-5', $storefront);
        $this->assertStringContainsString('focus:ring-2 focus:ring-primary/30', $storefront);
        $this->assertStringNotContainsString('border-0 bg-slate-50', $create);
        $this->assertStringNotContainsString('rounded border-slate-300 text-primary', $storefront);
    }
}
