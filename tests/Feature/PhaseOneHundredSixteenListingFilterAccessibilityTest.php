<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixteenListingFilterAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_listing_filters_have_accessible_names_and_mobile_relationships(): void
    {
        $response = $this->get(route('listings.index'));

        $response->assertOk();
        $response->assertSee('aria-controls="listing-filters"', false);
        $response->assertSee(':aria-expanded="filtersOpen.toString()"', false);
        $response->assertSee('id="listing-filters"', false);
        $response->assertSee('for="listing-search"', false);
        $response->assertSee('for="listing-min-price"', false);
        $response->assertSee('for="listing-max-price"', false);
    }
}
