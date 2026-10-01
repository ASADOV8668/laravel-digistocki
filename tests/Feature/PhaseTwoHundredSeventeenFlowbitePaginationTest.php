<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredSeventeenFlowbitePaginationTest extends TestCase
{
    public function test_public_paginated_views_reference_the_flowbite_pagination_template(): void
    {
        foreach ([
            'dashboard.blade.php',
            'favorites/index.blade.php',
            'notifications/index.blade.php',
            'reports/index.blade.php',
            'storefront/show.blade.php',
            'listings/partials/results.blade.php',
        ] as $view) {
            $source = file_get_contents(resource_path("views/{$view}"));

            $this->assertStringContainsString('vendor.pagination.flowbite', $source);
        }
    }

    public function test_flowbite_pagination_template_has_rtl_and_accessible_states(): void
    {
        $source = file_get_contents(resource_path('views/vendor/pagination/flowbite.blade.php'));

        $this->assertStringContainsString('aria-label="صفحه‌بندی"', $source);
        $this->assertStringContainsString('aria-current="page"', $source);
        $this->assertStringContainsString('aria-disabled="true"', $source);
        $this->assertStringContainsString('focus:ring-2 focus:ring-primary/30', $source);
    }
}
