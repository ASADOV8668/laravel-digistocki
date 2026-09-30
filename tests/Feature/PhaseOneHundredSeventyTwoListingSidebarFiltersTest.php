<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class PhaseOneHundredSeventyTwoListingSidebarFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_filter_sidebar_has_ajax_location_model_and_dynamic_attribute_controls(): void
    {
        $this->seed(CatalogSeeder::class);
        Province::create(['name' => 'استان فیلتر', 'tel_prefix' => '31']);

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('id="listing-filters"', false)
            ->assertSee('id="listing-model"', false)
            ->assertSee('id="listing-province"', false)
            ->assertSee('id="listing-city"', false)
            ->assertSee('listingSearch(', false)
            ->assertSee('loadCities()', false)
            ->assertSee('x-model="minPrice"', false)
            ->assertSee('x-model="maxPrice"', false)
            ->assertSee('filters[', false)
            ->assertSee('listing-min-price', false)
            ->assertSee('listing-max-price', false);

        $view = file_get_contents(resource_path('views/listings/index.blade.php'));
        $this->assertNotFalse($view);
        $this->assertStringContainsString('url("/listings/models")', $view);

        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertNotFalse($script);
        $this->assertStringContainsString('citiesController?.abort()', $script);
    }

    public function test_listing_sidebar_filters_results_by_province_and_city(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $province = Province::create(['name' => 'استان هدف', 'tel_prefix' => '32']);
        $county = County::create(['name' => 'شهرستان هدف', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر هدف', 'province_id' => $province->id, 'county_id' => $county->id]);
        $seller = User::factory()->create();
        $matching = $this->listing($seller, $brand->id, $model->id, $province->id, $city->id, 'آگهی استان هدف');
        $this->listing($seller, $brand->id, $model->id, null, null, 'آگهی بدون موقعیت');

        $response = $this->get(route('listings.index', [
            'q' => 'هدف',
            'phone_model_id' => $model->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'min_price' => 100,
            'max_price' => 2000000,
        ]));

        $response->assertOk()->assertSee($matching->title)->assertDontSee('آگهی بدون موقعیت');
        $this->assertSame($province->id, (int) $response->viewData('listings')->getCollection()->first()->province_id);
    }

    private function listing(User $seller, int $brandId, int $modelId, ?int $provinceId, ?int $cityId, string $title): Listing
    {
        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
