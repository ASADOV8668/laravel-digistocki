<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSeventyFiveReportOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_user_cannot_report_their_own_listing_through_the_endpoint(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner);

        $this->actingAs($owner)
            ->post(route('listings.report', $listing), ['reason' => 'آگهی تکراری'])
            ->assertStatus(422);

        $this->assertDatabaseCount('reports', 0);
    }

    private function listing(User $owner): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی مالک برای تست گزارش',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
