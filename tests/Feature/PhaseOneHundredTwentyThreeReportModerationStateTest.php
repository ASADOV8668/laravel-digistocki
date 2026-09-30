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

class PhaseOneHundredTwentyThreeReportModerationStateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        $this->admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $this->admin->save();
    }

    public function test_terminal_report_status_cannot_be_changed(): void
    {
        $report = $this->makeReport('resolved');

        $this->actingAs($this->admin)
            ->patch(route('admin.reports.status', $report), ['status' => 'rejected'])
            ->assertStatus(422);

        $this->assertSame('resolved', $report->refresh()->status);
    }

    public function test_pending_report_can_follow_the_moderation_workflow(): void
    {
        $report = $this->makeReport('pending');

        $this->actingAs($this->admin)
            ->patch(route('admin.reports.status', $report), ['status' => 'reviewed'])
            ->assertRedirect();
        $this->assertSame('reviewed', $report->refresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.reports.status', $report), ['status' => 'resolved'])
            ->assertRedirect();
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
            'title' => 'آگهی گزارش moderation',
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
