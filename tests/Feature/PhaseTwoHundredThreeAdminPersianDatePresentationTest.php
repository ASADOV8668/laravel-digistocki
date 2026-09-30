<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use App\Support\PersianDate;
use Carbon\Carbon;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoHundredThreeAdminPersianDatePresentationTest extends TestCase
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

    public function test_admin_listing_table_displays_jalali_creation_and_publication_dates(): void
    {
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $owner = User::factory()->create();
        $createdAt = Carbon::create(2026, 9, 23, 12);
        $publishedAt = $createdAt->copy()->addDay();

        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تاریخ شمسی مدیریت',
            'slug' => Str::uuid(),
            'price' => 42000000,
            'status' => ListingStatus::Approved,
            'published_at' => $publishedAt,
        ]);
        $listing->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        $this->actingAs($this->admin)
            ->get(route('admin.listings.index'))
            ->assertOk()
            ->assertSee(PersianDate::format($createdAt))
            ->assertSee(PersianDate::human($createdAt))
            ->assertSee(PersianDate::format($publishedAt));
    }

    public function test_admin_report_table_displays_jalali_creation_date(): void
    {
        $owner = User::factory()->create();
        $reporter = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی گزارش‌شده',
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => ListingStatus::Approved,
        ]);
        $createdAt = Carbon::create(2026, 9, 23, 12);
        $report = Report::create([
            'listing_id' => $listing->id,
            'user_id' => $reporter->id,
            'reason' => 'محتوای نادرست',
            'description' => 'گزارش آزمایشی',
            'status' => 'pending',
        ]);
        $report->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(PersianDate::format($createdAt))
            ->assertSee(PersianDate::human($createdAt));
    }
}
