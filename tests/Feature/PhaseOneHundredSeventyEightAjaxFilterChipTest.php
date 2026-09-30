<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSeventyEightAjaxFilterChipTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_chip_controls_are_ajax_enabled_and_state_sync_is_defined(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get(route('listings.index', ['sort' => 'price_asc']))
            ->assertOk()
            ->assertSee('listing-filter-chip', false);

        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertNotFalse($script);
        $this->assertStringContainsString('.listing-pagination a, .listing-filter-chip', $script);
        $this->assertStringContainsString('syncFormFromUrl', $script);
        $this->assertStringContainsString('filters[', $script);
    }
}
