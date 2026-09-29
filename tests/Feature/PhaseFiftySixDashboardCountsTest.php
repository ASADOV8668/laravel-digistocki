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

class PhaseFiftySixDashboardCountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_dashboard_counts_all_statuses_for_only_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $brand = Brand::firstOrFail();
        $modelId = $brand->phoneModels()->firstOrFail()->id;

        $this->listing($user, $brand->id, $modelId, ListingStatus::Pending);
        $this->listing($user, $brand->id, $modelId, ListingStatus::Pending);
        $this->listing($user, $brand->id, $modelId, ListingStatus::Approved);
        $this->listing($other, $brand->id, $modelId, ListingStatus::Rejected);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('counts', fn ($counts) => $counts->get('pending') === 2
                && $counts->get('approved') === 1
                && $counts->get('rejected') === 0
                && $counts->get('sold') === 0
                && $counts->get('expired') === 0);
    }

    private function listing(User $user, int $brandId, int $modelId, ListingStatus $status): Listing
    {
        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => 'آگهی شمارش '.Str::random(8),
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => $status,
        ]);
    }
}
