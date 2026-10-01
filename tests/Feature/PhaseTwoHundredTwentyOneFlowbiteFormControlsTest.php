<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyOneFlowbiteFormControlsTest extends TestCase
{
    public function test_public_form_controls_have_flowbite_friendly_shared_styles(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $listingIndex = file_get_contents(resource_path('views/listings/index.blade.php'));
        $listingCreate = file_get_contents(resource_path('views/listings/create.blade.php'));
        $home = file_get_contents(resource_path('views/welcome.blade.php'));

        $this->assertStringContainsString('.public-select', $css);
        $this->assertStringContainsString('.public-check', $css);
        $this->assertStringContainsString('class="public-select"', $listingIndex);
        $this->assertStringContainsString('class="public-check"', $listingIndex);
        $this->assertStringContainsString('class="public-check"', $listingCreate);
        $this->assertStringContainsString('class="public-select"', $home);
        $this->assertStringContainsString('focus:ring-2 focus:ring-primary/30', $home);
    }
}
