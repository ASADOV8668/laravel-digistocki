<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyEightFlowbiteNavigationTooltipTest extends TestCase
{
    public function test_public_icon_navigation_controls_have_flowbite_tooltips(): void
    {
        $topBar = file_get_contents(resource_path('views/components/top-bar.blade.php'));
        $sidebar = file_get_contents(resource_path('views/components/sidebar.blade.php'));

        $this->assertStringContainsString('data-tooltip-target="public-menu-tooltip"', $topBar);
        $this->assertStringContainsString('data-tooltip-target="public-notifications-tooltip"', $topBar);
        $this->assertStringContainsString('data-tooltip-target="public-back-tooltip"', $topBar);
        $this->assertStringContainsString('role="tooltip"', $topBar);
        $this->assertStringContainsString('data-tooltip-target="public-sidebar-close-tooltip"', $sidebar);
        $this->assertStringContainsString('focus:ring-4 focus:ring-primary/20', $sidebar);
    }
}
