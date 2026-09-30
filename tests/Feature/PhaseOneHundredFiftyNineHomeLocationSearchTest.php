<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class PhaseOneHundredFiftyNineHomeLocationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_all_location_options_and_bilingual_autocomplete(): void
    {
        $this->seed(CatalogSeeder::class);
        Province::create(['name' => 'استان نمونه', 'tel_prefix' => '11']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('در کدام استان و شهر هستید؟')
            ->assertSee('همه استان‌ها')
            ->assertSee('همه شهرها')
            ->assertSee('homeSearch(', false);

        $view = file_get_contents(resource_path('views/welcome.blade.php'));

        $this->assertNotFalse($view);
        $this->assertStringContainsString('route("listings.search.suggestions")', $view);
    }

    public function test_listing_feed_filters_home_search_by_province_and_city(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $seller = User::factory()->create();
        $province = Province::create(['name' => 'استان فیلتر', 'tel_prefix' => '12']);
        $county = County::create(['name' => 'شهرستان فیلتر', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر فیلتر', 'province_id' => $province->id, 'county_id' => $county->id]);
        $otherProvince = Province::create(['name' => 'استان دیگر', 'tel_prefix' => '13']);

        $matching = $this->listing($seller, $brand, $model, $province->id, $city->id, 'آگهی شهر فیلتر');
        $this->listing($seller, $brand, $model, $otherProvince->id, null, 'آگهی شهر دیگر');

        $this->get(route('listings.index', ['province_id' => $province->id, 'city_id' => $city->id]))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee('آگهی شهر دیگر');
    }

    private function listing(User $seller, Brand $brand, $model, int $provinceId, ?int $cityId, string $title): Listing
    {
        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'title' => $title,
            'slug' => str()->slug($title),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }
}
