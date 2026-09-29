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

class PhaseSeventyThreeUserReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_user_can_only_see_their_own_reports(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $listing = $this->listing('آگهی گزارش من');
        $otherListing = $this->listing('آگهی گزارش کاربر دیگر');
        Report::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'reason' => 'اطلاعات نادرست', 'status' => 'pending']);
        Report::create(['user_id' => $otherUser->id, 'listing_id' => $otherListing->id, 'reason' => 'آگهی تکراری', 'status' => 'resolved']);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('آگهی گزارش من')
            ->assertDontSee('آگهی گزارش کاربر دیگر');
    }

    public function test_deleted_listing_report_has_a_safe_fallback(): void
    {
        $user = User::factory()->create();
        $listing = $this->listing('آگهی حذف‌شده گزارش‌شده');
        Report::create(['user_id' => $user->id, 'listing_id' => $listing->id, 'reason' => 'محتوای نامناسب', 'status' => 'reviewed']);
        $listing->delete();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('آگهی حذف‌شده');
    }

    private function listing(string $title): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);
    }
}
