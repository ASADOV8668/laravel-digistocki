<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseNinetyFiveAutocompleteRaceTest extends TestCase
{
    public function test_autocomplete_requests_are_abortable_and_ignore_abort_errors(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($source);
        $this->assertStringContainsString('new AbortController()', $source);
        $this->assertStringContainsString('signal: controller.signal', $source);
        $this->assertStringContainsString("error.name !== 'AbortError'", $source);
        $this->assertStringContainsString('this.requestController === controller', $source);
    }
}
