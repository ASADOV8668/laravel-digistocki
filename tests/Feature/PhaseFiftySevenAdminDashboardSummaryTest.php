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

class PhaseFiftySevenAdminDashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_and_trend_are_built_from_aggregates(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $recent = $this->listing($admin, $brand->id, $model->id, ListingStatus::Approved, now()->subDay());
        $this->listing($admin, $brand->id, $model->id, ListingStatus::Pending, now()->subDays(2));
        $old = $this->listing($admin, $brand->id, $model->id, ListingStatus::Rejected, now()->subDays(8));
        Report::create(['listing_id' => $recent->id, 'user_id' => $admin->id, 'reason' => 'گزارش تست']);
        Report::create(['listing_id' => $old->id, 'user_id' => $admin->id, 'reason' => 'گزارش حل‌شده', 'status' => 'resolved']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('listingsCount', 3)
            ->assertViewHas('pendingReportsCount', 1)
            ->assertViewHas('resolvedReportsCount', 1)
            ->assertViewHas('listingTrend', fn ($trend) => $trend->sum('count') === 2);
    }

    private function listing(User $user, int $brandId, int $modelId, ListingStatus $status, $createdAt): Listing
    {
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => 'گزارش aggregate '.Str::random(6),
            'slug' => Str::uuid()->toString(),
            'price' => 1000000,
            'status' => $status,
        ]);

        $listing->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        return $listing;
    }
}
