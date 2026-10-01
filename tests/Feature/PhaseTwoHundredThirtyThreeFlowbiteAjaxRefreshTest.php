<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyThreeFlowbiteAjaxRefreshTest extends TestCase
{
    public function test_ajax_listing_refresh_reinitializes_flowbite_tooltips(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("import { initTooltips } from 'flowbite';", $javascript);
        $this->assertStringContainsString('window.refreshFlowbiteTooltips = () => initTooltips();', $javascript);
        $this->assertStringContainsString('window.refreshFlowbiteTooltips?.();', $javascript);
        $this->assertStringContainsString('container.innerHTML = data.html;', $javascript);
    }
}
