<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEightySevenWizardAjaxRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_listing_wizard_cancels_stale_attribute_and_city_requests(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $wizard = substr($script, strpos($script, 'window.listingWizard ='));
        $this->assertNotFalse($wizard);
        $this->assertStringContainsString('attributesController: null', $wizard);
        $this->assertStringContainsString('citiesController: null', $wizard);
        $this->assertSame(2, substr_count($wizard, 'signal: controller.signal'));
        $this->assertStringContainsString("if (error.name !== 'AbortError') this.attributes = [];", $wizard);
        $this->assertStringContainsString("if (error.name !== 'AbortError') this.cities = [];", $wizard);
        $this->assertStringContainsString('this.loadCities(false)', $wizard);
    }
}
