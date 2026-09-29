<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class PhaseFiftyEightListingLocationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_city_must_belong_to_the_selected_province_on_store(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $province = Province::create(['name' => 'استان اول', 'tel_prefix' => '11']);
        $provinceTwo = Province::create(['name' => 'استان دوم', 'tel_prefix' => '22']);
        $city = $this->city($provinceTwo, 'شهر دوم');

        $this->actingAs($user)
            ->post(route('listings.store'), [
                'brand_id' => $brand->id,
                'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
                'title' => 'آگهی با موقعیت نادرست',
                'price' => 1000000,
                'province_id' => $province->id,
                'city_id' => $city->id,
            ])
            ->assertSessionHasErrors('city_id');

        $this->assertDatabaseMissing('listings', ['title' => 'آگهی با موقعیت نادرست']);
    }

    private function city(Province $province, string $name): City
    {
        $county = County::create(['name' => $name.' شهرستان', 'province_id' => $province->id]);

        return City::create(['name' => $name, 'province_id' => $province->id, 'county_id' => $county->id]);
    }
}
