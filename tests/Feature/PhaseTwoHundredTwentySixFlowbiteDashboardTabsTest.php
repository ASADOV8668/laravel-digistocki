<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentySixFlowbiteDashboardTabsTest extends TestCase
{
    public function test_dashboard_listing_filters_use_accessible_flowbite_tab_states(): void
    {
        $dashboard = file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertStringContainsString('role="tablist"', $dashboard);
        $this->assertStringContainsString('aria-label="فیلتر وضعیت آگهی‌ها"', $dashboard);
        $this->assertStringContainsString('role="tab"', $dashboard);
        $this->assertStringContainsString('aria-selected="{{ ! $status ?', $dashboard);
        $this->assertStringContainsString('id="dashboard-listings"', $dashboard);
        $this->assertStringContainsString('role="tabpanel"', $dashboard);
        $this->assertStringContainsString('focus:ring-4 focus:ring-primary/20', $dashboard);
    }
}
