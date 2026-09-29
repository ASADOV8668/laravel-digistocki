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

class PhaseFiftyThreeListingUpdateDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_update_rejects_a_recent_duplicate_but_allows_the_same_listing(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $otherListing = $this->listing($user, $brand->id, $model->id, 'آگهی دیگر');
        $target = $this->listing($user, $brand->id, $model->id, 'آگهی هدف', ListingStatus::Rejected);

        $this->actingAs($user)
            ->put(route('listings.update', $target), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی هدف ویرایش‌شده',
                'price' => 2000000,
            ])
            ->assertStatus(422)
            ->assertSee('آگهی دیگری');

        $otherListing->delete();

        $this->actingAs($user)
            ->put(route('listings.update', $target), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی هدف ویرایش‌شده',
                'price' => 2000000,
            ])
            ->assertRedirect();
    }

    private function listing(User $user, int $brandId, int $modelId, string $title, ListingStatus $status = ListingStatus::Pending): Listing
    {
        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => $status,
        ]);
    }
}
