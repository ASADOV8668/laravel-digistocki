<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredFifteenFlowbitePublicPagesTest extends TestCase
{
    public function test_listing_filter_uses_flowbite_drawer_hooks(): void
    {
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('data-drawer-target="listing-filters"', false)
            ->assertSee('data-drawer-show="listing-filters"', false)
            ->assertSee('data-drawer-backdrop="true"', false)
            ->assertSee('data-drawer-hide="listing-filters"', false);
    }

    public function test_public_account_pages_use_flowbite_card_radius_and_borders(): void
    {
        $this->assertStringNotContainsString('rounded-3xl', file_get_contents(resource_path('views/dashboard.blade.php')));
        foreach (['favorites/index', 'notifications/index', 'profile/edit', 'support/index'] as $view) {
            $source = file_get_contents(resource_path("views/{$view}.blade.php"));

            $this->assertStringContainsString('rounded-lg', $source);
            $this->assertStringContainsString('border', $source);
        }
    }
}
