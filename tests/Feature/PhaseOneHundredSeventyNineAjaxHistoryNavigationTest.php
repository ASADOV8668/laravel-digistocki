<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSeventyNineAjaxHistoryNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_history_uses_ajax_results_and_syncs_filter_state(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString("this.popstateHandler = () => this.fetchResults(window.location.href, 'none', true);", $script);
        $this->assertStringContainsString("if (historyMode === 'none')", $script);
        $this->assertStringContainsString("document.getElementById('listing-results')?.scrollIntoView", $script);
        $this->assertStringContainsString('await this.syncFormFromUrl(new URL(url, window.location.href));', $script);
        $this->assertStringNotContainsString('this.popstateHandler = () => window.location.reload();', $script);
    }
}
