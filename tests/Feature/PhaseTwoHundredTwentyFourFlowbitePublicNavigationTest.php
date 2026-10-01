<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyFourFlowbitePublicNavigationTest extends TestCase
{
    public function test_public_navigation_uses_flowbite_drawer_and_accessible_active_states(): void
    {
        $sidebar = file_get_contents(resource_path('views/components/sidebar.blade.php'));
        $topBar = file_get_contents(resource_path('views/components/top-bar.blade.php'));
        $bottomNav = file_get_contents(resource_path('views/components/bottom-nav.blade.php'));

        $this->assertStringContainsString('data-drawer-placement="left"', $sidebar);
        $this->assertStringContainsString('role="dialog"', $sidebar);
        $this->assertStringContainsString('aria-modal="true"', $sidebar);
        $this->assertStringContainsString('data-drawer-placement="left"', $topBar);
        $this->assertStringContainsString('aria-current="page"', $bottomNav);
        $this->assertStringContainsString('focus:ring-4 focus:ring-primary/20', $bottomNav);
    }
}
