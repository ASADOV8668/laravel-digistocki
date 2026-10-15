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

class PhaseTwoHundredFiftySixUserReportDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_user_can_delete_their_own_report(): void
    {
        $user = User::factory()->create();
        $report = Report::create([
            'user_id' => $user->id,
            'listing_id' => $this->listing()->id,
            'reason' => 'گزارش قابل حذف',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->delete(route('reports.destroy', $report))
            ->assertRedirect(route('reports.index'))
            ->assertSessionHas('status', 'گزارش حذف شد.');

        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_user_cannot_delete_another_users_report(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $report = Report::create([
            'user_id' => $owner->id,
            'listing_id' => $this->listing()->id,
            'reason' => 'گزارش متعلق به کاربر دیگر',
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)
            ->delete(route('reports.destroy', $report))
            ->assertNotFound();

        $this->assertDatabaseHas('reports', ['id' => $report->id]);
    }

    public function test_reports_page_exposes_a_delete_action(): void
    {
        $user = User::factory()->create();
        $report = Report::create([
            'user_id' => $user->id,
            'listing_id' => $this->listing()->id,
            'reason' => 'گزارش دارای دکمه حذف',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee(route('reports.destroy', $report), false)
            ->assertSee('حذف گزارش', false);
    }

    private function listing(): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی گزارش تست',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);
    }
}
