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

class PhaseOneHundredSeventeenAdminListingSearchTest extends TestCase
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

    public function test_admin_can_find_listings_by_bilingual_brand_and_model_names(): void
    {
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->where('name', 'iPhone 13 Pro')->firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی با عنوان عمومی',
            'slug' => Str::uuid(),
            'price' => 42000000,
            'status' => ListingStatus::Pending,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.listings.index', ['q' => 'iPhone 13']))
            ->assertOk()
            ->assertSee($listing->title);

        $this->actingAs($this->admin)
            ->get(route('admin.listings.index', ['q' => 'آیفون']))
            ->assertOk()
            ->assertSee($listing->title);
    }
}
