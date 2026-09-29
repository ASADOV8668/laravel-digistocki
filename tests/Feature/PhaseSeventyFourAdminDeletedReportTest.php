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

class PhaseSeventyFourAdminDeletedReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_reports_page_handles_a_deleted_listing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی حذف‌شده مدیریت',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);
        Report::create(['user_id' => $reporter->id, 'listing_id' => $listing->id, 'reason' => 'آگهی تکراری', 'status' => 'pending']);
        $listing->delete();

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('آگهی حذف‌شده');
    }
}
