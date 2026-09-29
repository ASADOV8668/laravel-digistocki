<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class PhaseNineListingWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_create_page_exposes_four_step_wizard_and_location_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('listings.create'))
            ->assertOk()
            ->assertSee('۱. مدل')
            ->assertSee('۲. مشخصات')
            ->assertSee('۳. اطلاعات')
            ->assertSee('۴. ارسال')
            ->assertSee('استان')
            ->assertSee('شهر');
    }

    public function test_all_model_attributes_endpoint_supports_listing_creation_fields(): void
    {
        $model = PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail();

        $this->getJson(route('listings.models.attributes', [$model, 'all' => 1]))
            ->assertOk()
            ->assertJsonFragment(['name' => 'حافظه داخلی'])
            ->assertJsonFragment(['name' => 'جعبه و لوازم', 'type' => 'boolean']);
    }

    public function test_city_endpoint_returns_only_cities_of_selected_province(): void
    {
        $province = Province::create(['name' => 'استان تست', 'tel_prefix' => '00']);
        $county = County::create(['name' => 'شهرستان تست', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر تست', 'province_id' => $province->id, 'county_id' => $county->id]);
        $otherProvince = Province::create(['name' => 'استان دیگر', 'tel_prefix' => '01']);
        $otherCounty = County::create(['name' => 'شهرستان دیگر', 'province_id' => $otherProvince->id]);
        City::create(['name' => 'شهر دیگر', 'province_id' => $otherProvince->id, 'county_id' => $otherCounty->id]);

        $this->getJson(route('locations.provinces.cities', $province))
            ->assertOk()
            ->assertJsonFragment(['id' => $city->id, 'name' => 'شهر تست'])
            ->assertJsonMissing(['name' => 'شهر دیگر']);
    }

    public function test_wizard_page_has_a_model_from_the_bilingual_catalog(): void
    {
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('listings.create'))
            ->assertSee($brand->name)
            ->assertSee('آیفون 13 پرو')
            ->assertSee('iPhone 13 Pro');
    }
}
