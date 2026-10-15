<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredEighteenFlowbiteAlertsAccordionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_alert_component_and_listing_report_use_flowbite_hooks(): void
    {
        $alert = file_get_contents(resource_path('views/components/flowbite-alert.blade.php'));
        $listing = file_get_contents(resource_path('views/listings/show.blade.php'));
        $reports = file_get_contents(resource_path('views/admin/reports/index.blade.php'));

        $this->assertStringContainsString('data-dismiss-target', $alert);
        $this->assertStringContainsString('aria-live="polite"', $alert);
        $this->assertStringContainsString('data-accordion="collapse"', $listing);
        $this->assertStringContainsString('data-accordion-target="#listing-report-panel"', $listing);
        $this->assertStringContainsString("show-notification', { title: 'ورود لازم است'", $listing);
        $this->assertStringContainsString('برای ثبت گزارش آگهی ابتدا وارد سیستم شوید.', $listing);
        $this->assertStringContainsString('route(\'listings.show\', $report->listing)', $reports);
        $this->assertStringContainsString('target="_blank"', $reports);
    }

    public function test_authenticated_listing_page_renders_the_flowbite_report_accordion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('listings.index'))
            ->assertOk()
            ->assertDontSee('data-accordion="collapse"', false);
    }
}
