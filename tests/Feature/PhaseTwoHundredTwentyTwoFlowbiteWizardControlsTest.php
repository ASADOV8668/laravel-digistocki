<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyTwoFlowbiteWizardControlsTest extends TestCase
{
    public function test_public_listing_and_storefront_forms_use_shared_flowbite_controls(): void
    {
        $create = file_get_contents(resource_path('views/listings/create.blade.php'));
        $edit = file_get_contents(resource_path('views/listings/edit.blade.php'));
        $storefront = file_get_contents(resource_path('views/storefront/edit.blade.php'));

        $this->assertGreaterThanOrEqual(4, substr_count($create, 'class="public-select"'));
        $this->assertStringContainsString('class="public-check"', $create);
        $this->assertStringContainsString('class="public-select"', $edit);
        $this->assertStringContainsString('class="public-select"', $storefront);
        $this->assertStringContainsString('class="public-file"', $storefront);
        $this->assertStringContainsString('focus:ring-2 focus:ring-primary/30', $storefront);
    }
}
