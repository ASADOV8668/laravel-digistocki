<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyThreeListingDesktopLayoutTest extends TestCase
{
    public function test_listing_results_use_two_desktop_columns_and_price_range_is_left_to_right(): void
    {
        $resultsView = file_get_contents(resource_path('views/listings/partials/results.blade.php'));
        $listingView = file_get_contents(resource_path('views/listings/index.blade.php'));

        $this->assertIsString($resultsView);
        $this->assertIsString($listingView);
        $this->assertStringContainsString('xl:grid-cols-2', $resultsView);
        $this->assertStringNotContainsString('xl:grid-cols-3', $resultsView);
        $this->assertStringContainsString('data-price-range="listing" dir="ltr"', $listingView);
        $this->assertStringContainsString('<div class="relative h-6" dir="ltr">', $listingView);
    }
}
