<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFiftyFiveNetworkStatusTest extends TestCase
{
    public function test_public_layout_contains_an_accessible_offline_status_banner(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('networkStatus()', false)
            ->assertSee('اتصال اینترنت قطع است')
            ->assertSee('aria-live="polite"', false);
    }

    public function test_network_status_listens_to_browser_connectivity_events(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString('window.networkStatus', $script);
        $this->assertStringContainsString("window.addEventListener('online'", $script);
        $this->assertStringContainsString("window.addEventListener('offline'", $script);
    }

    public function test_admin_layout_does_not_render_public_network_banner(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertNotFalse($layout);
        $this->assertStringNotContainsString('networkStatus()', $layout);
    }
}
