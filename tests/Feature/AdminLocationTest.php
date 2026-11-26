<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class AdminLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_rename_locations_without_changing_their_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('admin.locations.provinces.store'), ['name' => 'استان آزمایشی', 'tel_prefix' => '۰۲۱'])
            ->assertSessionHasNoErrors();

        $province = Province::query()->where('name', 'استان آزمایشی')->firstOrFail();
        $this->assertSame('021', $province->tel_prefix);
        $this->patch(route('admin.locations.provinces.update', $province), [
            'name' => 'استان ویرایش‌شده', 'tel_prefix' => '021',
        ])->assertSessionHasNoErrors();

        $cityData = ['name' => 'شهر آزمایشی', 'county' => 'شهرستان آزمایشی'];
        $this->post(route('admin.locations.cities.store', $province), $cityData)->assertSessionHasNoErrors();
        $this->post(route('admin.locations.cities.store', $province), $cityData)->assertSessionHasErrors('name');
        $city = City::query()->where('province_id', $province->id)->sole();

        $this->patch(route('admin.locations.cities.update', $city), ['name' => 'شهر ویرایش‌شده'])
            ->assertSessionHasNoErrors();

        $this->get(route('admin.locations.index', ['province' => $province->id]))
            ->assertOk()
            ->assertSee('استان ویرایش‌شده')
            ->assertSee('شهر ویرایش‌شده');

        $this->getJson(route('locations.provinces.cities', $province))
            ->assertExactJson([['id' => $city->id, 'name' => 'شهر ویرایش‌شده', 'province_id' => $province->id]]);
    }

    public function test_only_unused_provinces_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $province = Province::query()->create(['name' => 'استان دارای شهر', 'tel_prefix' => '021']);
        $this->post(route('admin.locations.cities.store', $province), [
            'name' => 'شهر آزمایشی', 'county' => 'شهرستان آزمایشی',
        ])->assertSessionHasNoErrors();
        $this->delete(route('admin.locations.provinces.destroy', $province))->assertSessionHasErrors('province');
        $this->assertModelExists($province);

        $emptyProvince = Province::query()->create(['name' => 'استان خالی', 'tel_prefix' => '022']);
        $this->delete(route('admin.locations.provinces.destroy', $emptyProvince))->assertSessionHasNoErrors();
        $this->assertModelMissing($emptyProvince);
    }

    public function test_regular_user_cannot_manage_locations(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.locations.index'))
            ->assertForbidden();

        $this->post(route('admin.locations.provinces.store'), ['name' => 'استان آزمایشی', 'tel_prefix' => '021'])
            ->assertForbidden();
        $this->assertDatabaseMissing('provinces', ['name' => 'استان آزمایشی']);
    }
}
