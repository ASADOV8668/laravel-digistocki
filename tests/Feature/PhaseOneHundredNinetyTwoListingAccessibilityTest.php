<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredNinetyTwoListingAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_filters_and_ajax_results_expose_semantic_accessibility_hooks(): void
    {
        $view = file_get_contents(resource_path('views/listings/index.blade.php'));
        $card = file_get_contents(resource_path('views/components/listing-card.blade.php'));

        $this->assertNotFalse($view);
        $this->assertNotFalse($card);
        $this->assertStringContainsString('role="region"', $view);
        $this->assertStringContainsString('aria-labelledby="listing-filters-title"', $view);
        $this->assertStringContainsString('@keydown.escape.window="filtersOpen = false"', $view);
        $this->assertStringContainsString('role="search"', $view);
        $this->assertStringContainsString('aria-live="polite"', $view);
        $this->assertStringContainsString('aria-atomic="false"', $view);
        $this->assertStringContainsString('decoding="async"', $card);
    }
}
