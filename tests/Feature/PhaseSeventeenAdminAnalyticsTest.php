<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSeventeenAdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_seven_day_listing_trend_and_status_summary(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $this->listing($admin, $brand, $model, ListingStatus::Approved, now()->subDays(2));
        $this->listing($admin, $brand, $model, ListingStatus::Expired, now()->subDay());

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('گزارش ثبت آگهی در ۷ روز گذشته')
            ->assertSee('خلاصه وضعیت')
            ->assertSee('منقضی‌شده');
    }

    private function listing(User $user, Brand $brand, PhoneModel $model, ListingStatus $status, $createdAt): Listing
    {
        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'گزارش '.Str::random(6),
            'slug' => Str::uuid()->toString(),
            'price' => 1000000,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
