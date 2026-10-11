<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoHundredFortySixAdminReportAjaxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_update_report_status_with_json(): void
    {
        $report = $this->makeReport('pending');

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.status', $report), ['status' => 'reviewed'])
            ->assertOk()
            ->assertJson([
                'status' => 'reviewed',
                'status_label' => 'بررسی‌شده',
                'terminal' => false,
            ]);

        $this->assertSame('reviewed', $report->refresh()->status);

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.status', $report), ['status' => 'resolved'])
            ->assertOk()
            ->assertJson([
                'status' => 'resolved',
                'status_label' => 'حل‌شده',
                'terminal' => true,
            ]);

        $this->assertSame('resolved', $report->refresh()->status);
    }

    public function test_terminal_report_cannot_be_changed_with_json(): void
    {
        $report = $this->makeReport('resolved');

        $this->actingAs($this->admin)
            ->patchJson(route('admin.reports.status', $report), ['status' => 'rejected'])
            ->assertStatus(422);

        $this->assertSame('resolved', $report->refresh()->status);
    }

    private function makeReport(string $status): Report
    {
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی گزارش AJAX',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);

        return Report::create([
            'listing_id' => $listing->id,
            'user_id' => $owner->id,
            'reason' => 'اطلاعات نادرست',
            'status' => $status,
        ]);
    }
}
