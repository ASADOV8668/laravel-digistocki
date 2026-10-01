<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyFiveFlowbiteLoadingTest extends TestCase
{
    public function test_public_ajax_loading_uses_shared_flowbite_status_component(): void
    {
        $component = file_get_contents(resource_path('views/components/flowbite-loading.blade.php'));
        $listingIndex = file_get_contents(resource_path('views/listings/index.blade.php'));

        $this->assertStringContainsString('role="status"', $component);
        $this->assertStringContainsString('aria-live="polite"', $component);
        $this->assertStringContainsString('animate-spin', $component);
        $this->assertStringContainsString('<x-flowbite-loading label="در حال به‌روزرسانی نتایج..." />', $listingIndex);
        $this->assertStringContainsString('x-show="resultsLoading"', $listingIndex);
    }
}
