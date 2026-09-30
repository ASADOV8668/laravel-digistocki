<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEightyAjaxAttributeRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_attribute_requests_are_cancelled_before_a_new_response_can_win(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString('attributesController: null', $script);
        $this->assertStringContainsString('this.cancelAttributeRequest();', $script);
        $this->assertStringContainsString('signal: controller.signal,', $script);
        $this->assertStringContainsString("if (error.name !== 'AbortError') this.attributes = [];", $script);
        $this->assertStringContainsString('if (this.attributesController === controller)', $script);
    }
}
