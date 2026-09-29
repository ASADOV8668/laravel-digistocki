<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseNinetyTwoWizardStateTest extends TestCase
{
    public function test_listing_wizard_preserves_initial_values_but_resets_values_on_model_change(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($source);
        $this->assertStringContainsString('this.loadAttributes(false)', $source);
        $this->assertStringContainsString('async loadAttributes(resetValues = true)', $source);
        $this->assertStringContainsString('if (resetValues) this.values = {};', $source);
    }

    public function test_search_model_switch_clears_dependent_filters(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($source);
        $source = str_replace(["\r\n", "\r"], "\n", $source);
        $this->assertStringContainsString('if (previousModelId !== model.id) this.filters = {};', $source);
        $this->assertStringContainsString("this.filters = {};\n        this.open = false;", $source);
    }
}
