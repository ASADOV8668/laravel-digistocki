<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredFourteenFlowbitePublicContentTest extends TestCase
{
    public function test_home_page_uses_flowbite_search_controls(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rounded-lg border border-slate-200 bg-white p-5 shadow-sm', false)
            ->assertSee('focus:ring-4 focus:ring-primary-200', false)
            ->assertSee('x-data="homeSearch(', false);
    }

    public function test_public_auth_and_listing_views_use_flowbite_content_markers(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rounded-lg bg-primary px-5 py-2.5', false)
            ->assertSee('rounded-lg border border-slate-200 bg-white shadow-lg', false);

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('rounded-lg border border-slate-200', false);

        $this->assertStringContainsString('rounded-lg border border-slate-200 bg-white shadow-sm', file_get_contents(resource_path('views/components/listing-card.blade.php')));
        $this->assertStringContainsString('x-data="listingWizard(', file_get_contents(resource_path('views/listings/create.blade.php')));
        $this->assertStringContainsString('x-data="storefrontLocation(', file_get_contents(resource_path('views/storefront/edit.blade.php')));
        $this->assertStringContainsString('x-data="shareLink(', file_get_contents(resource_path('views/storefront/show.blade.php')));
    }
}
