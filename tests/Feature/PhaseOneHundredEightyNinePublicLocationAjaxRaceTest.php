<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEightyNinePublicLocationAjaxRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_storefront_location_requests_are_cancelable(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $homeSearch = substr($script, strpos($script, 'window.homeSearch ='), strpos($script, 'window.storefrontLocation =') - strpos($script, 'window.homeSearch ='));
        $storefrontLocation = substr($script, strpos($script, 'window.storefrontLocation ='), strpos($script, 'window.shareLink =') - strpos($script, 'window.storefrontLocation ='));

        $this->assertStringContainsString('citiesController: null', $homeSearch);
        $this->assertStringContainsString('citiesController: null', $storefrontLocation);
        $this->assertStringContainsString('signal: controller.signal', $homeSearch);
        $this->assertStringContainsString('signal: controller.signal', $storefrontLocation);
        $this->assertStringContainsString("if (error.name !== 'AbortError') this.cities = [];", $homeSearch);
        $this->assertStringContainsString("if (error.name !== 'AbortError') this.cities = [];", $storefrontLocation);
        $this->assertStringContainsString('this.requestController?.abort()', $homeSearch);
    }
}
