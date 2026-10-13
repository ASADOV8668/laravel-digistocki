<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class PhaseTwoHundredEightyPublicGeolocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reverse_location_returns_the_nearest_database_city_and_province(): void
    {
        $province = Province::create(['name' => 'استان مکان‌یابی', 'tel_prefix' => '99']);
        $county = County::create(['name' => 'شهرستان مکان‌یابی', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر نزدیک', 'province_id' => $province->id, 'county_id' => $county->id]);
        $city->forceFill(['latitude' => 36.2605, 'longitude' => 59.6168])->save();

        $response = $this->getJson(route('locations.reverse', [
            'latitude' => 36.261,
            'longitude' => 59.617,
        ]));

        $response->assertOk()
            ->assertJsonPath('city_id', $city->id)
            ->assertJsonPath('province_id', $province->id)
            ->assertJsonPath('city_name', 'شهر نزدیک')
            ->assertJsonPath('province_name', 'استان مکان‌یابی')
            ->assertJsonStructure(['distance_km']);
    }

    public function test_public_location_scripts_request_a_fresh_position_after_permission_policy_is_enabled(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $this->assertSame(2, substr_count($script, 'maximumAge: 0'));
        $this->assertStringContainsString('window.isSecureContext', $script);
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(self), microphone=()');
    }
}
