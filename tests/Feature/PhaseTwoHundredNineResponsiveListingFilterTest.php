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

class PhaseTwoHundredNineResponsiveListingFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_filters_are_closed_drawer_with_a_floating_button_and_price_range(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی برای سقف قیمت',
            'slug' => Str::uuid(),
            'price' => 42500000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);

        $response = $this->get(route('listings.index'))->assertOk();
        $view = $response->getContent();

        $this->assertStringContainsString("filtersOpen ? 'translate-x-0' : '-translate-x-full'", $view);
        $this->assertStringContainsString('aria-label="باز کردن فیلترها"', $view);
        $this->assertStringContainsString('type="range"', $view);
        $this->assertStringContainsString('class="price-range', $view);
        $this->assertStringContainsString('syncPriceRange', file_get_contents(resource_path('js/app.js')));
        $this->assertSame(43000000, $response->viewData('priceCeiling'));
    }

    public function test_price_range_values_are_still_applied_by_the_server(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $seller = User::factory()->create();
        $cheap = $this->listing($seller, $brand->id, $model->id, 'گوشی ارزان', 1200000);
        $this->listing($seller, $brand->id, $model->id, 'گوشی گران', 9800000);

        $this->get(route('listings.index', ['min_price' => 1000000, 'max_price' => 2000000]))
            ->assertOk()
            ->assertSee($cheap->title)
            ->assertDontSee('گوشی گران');
    }

    private function listing(User $seller, int $brandId, int $modelId, string $title, int $price): Listing
    {
        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => $price,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
