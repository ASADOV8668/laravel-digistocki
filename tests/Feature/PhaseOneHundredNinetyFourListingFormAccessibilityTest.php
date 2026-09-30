<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredNinetyFourListingFormAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_listing_contact_and_report_controls_have_associated_labels(): void
    {
        $view = file_get_contents(resource_path('views/listings/show.blade.php'));

        $this->assertNotFalse($view);
        $this->assertStringContainsString('for="listing-contact-otp"', $view);
        $this->assertStringContainsString('id="listing-contact-otp"', $view);
        $this->assertStringContainsString('for="listing-report-reason"', $view);
        $this->assertStringContainsString('id="listing-report-reason"', $view);
        $this->assertStringContainsString('for="listing-report-description"', $view);
        $this->assertStringContainsString('id="listing-report-description"', $view);
    }
}
