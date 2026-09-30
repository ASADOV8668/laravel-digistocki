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

class PhaseOneHundredTwentyFourReportFilterValidationTest extends TestCase
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

    public function test_known_status_filter_is_applied(): void
    {
        $report = $this->makeReport('pending', 'گزارش فیلترشده');
        $otherReport = $this->makeReport('resolved', 'گزارش خارج از فیلتر');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($report->reason)
            ->assertDontSee($otherReport->reason);
    }

    public function test_unknown_status_filter_is_ignored_safely(): void
    {
        $report = $this->makeReport('pending', 'گزارش بدون فیلتر معتبر');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['status' => 'not-a-status']))
            ->assertOk()
            ->assertSee($report->reason);
    }

    private function makeReport(string $status, string $reason): Report
    {
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی فیلتر گزارش',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);

        return Report::create([
            'listing_id' => $listing->id,
            'user_id' => $owner->id,
            'reason' => $reason,
            'status' => $status,
        ]);
    }
}
