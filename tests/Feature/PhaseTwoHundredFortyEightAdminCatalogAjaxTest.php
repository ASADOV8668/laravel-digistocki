<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseTwoHundredFortyEightAdminCatalogAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_toggle_brand_model_and_attribute_with_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::firstOrFail();
        $model = PhoneModel::where('brand_id', $brand->id)->firstOrFail();
        $attribute = Attribute::firstOrFail();

        $this->actingAs($admin)
            ->patchJson(route('admin.brands.toggle', $brand), [])
            ->assertOk()
            ->assertJson(['is_active' => ! $brand->is_active, 'status_label' => 'غیرفعال']);

        $this->actingAs($admin)
            ->patchJson(route('admin.phone-models.toggle', $model), [])
            ->assertOk()
            ->assertJson(['is_active' => ! $model->is_active, 'status_label' => 'غیرفعال']);

        $this->actingAs($admin)
            ->patchJson(route('admin.attributes.toggle', $attribute), [])
            ->assertOk()
            ->assertJson(['is_active' => ! $attribute->is_active, 'status_label' => 'غیرفعال']);

        $this->assertFalse($brand->refresh()->is_active);
        $this->assertFalse($model->refresh()->is_active);
        $this->assertFalse($attribute->refresh()->is_active);
    }

    public function test_admin_can_manage_and_delete_catalog_entries_in_each_device_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mobileBrand = Brand::firstOrFail();
        $this->actingAs($admin);

        foreach (['laptop', 'tablet', 'smartwatch'] as $type) {
            $name = ucfirst($type).' Catalog';
            $this->post(route('admin.brands.store'), ['name' => $name, 'device_type' => $type])
                ->assertRedirect()->assertSessionHasNoErrors();
            $brand = Brand::where('name', $name)->where('device_type', $type)->sole();

            $this->post(route('admin.phone-series.store'), [
                'brand_id' => $brand->id, 'device_type' => $type, 'name' => $name.' Series',
            ])->assertRedirect()->assertSessionHasNoErrors();
            $series = $brand->phoneSeries()->sole();

            $model = ['brand_id' => $brand->id, 'device_type' => $type, 'name' => $name.' Model'];
            $this->postJson(route('admin.phone-models.store'), array_replace($model, ['brand_id' => $mobileBrand->id]))
                ->assertUnprocessable()->assertJsonValidationErrors('brand_id');
            $this->post(route('admin.phone-models.store'), $model + ['phone_series_id' => $series->id])
                ->assertRedirect()->assertSessionHasNoErrors();
            $savedModel = $brand->phoneModels()->sole();
            $this->assertEquals($series->id, $savedModel->phone_series_id);

            foreach (['brands' => $name, 'phone-series' => $series->name, 'phone-models' => $savedModel->name] as $section => $label) {
                $this->get(route('admin.'.$section.'.index', ['device_type' => $type]))
                    ->assertOk()->assertSeeText($label);
                $this->get(route('admin.'.$section.'.index', ['device_type' => 'mobile']))
                    ->assertOk()->assertDontSeeText($label);
            }

            $this->delete(route('admin.brands.destroy', $brand))->assertRedirect()->assertSessionHasNoErrors();
            $this->assertModelMissing($brand);
            $this->assertModelMissing($series);
            $this->assertModelMissing($savedModel);
        }
    }

    public function test_existing_mixed_catalog_keeps_its_series_and_listings_when_split(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mobileBrand = Brand::where('slug', 'apple')->sole();
        $series = $mobileBrand->phoneSeries()->firstOrFail();
        $tablet = $mobileBrand->phoneModels()->create([
            'name' => 'Legacy iPad', 'slug' => 'legacy-ipad', 'device_type' => 'tablet', 'phone_series_id' => $series->id,
        ]);
        $listingId = DB::table('listings')->insertGetId([
            'user_id' => $admin->id, 'brand_id' => $mobileBrand->id, 'phone_model_id' => $tablet->id,
            'title' => 'Legacy tablet listing', 'slug' => 'legacy-tablet-listing', 'price' => 100,
        ]);

        $migration = require database_path('migrations/2026_11_24_000100_separate_device_brand_catalogs.php');
        $migration->down();
        $migration->up();

        $tablet->refresh();
        $tabletBrand = $tablet->brand;
        $this->assertNotEquals($mobileBrand->id, $tabletBrand->id);
        $this->assertSame('tablet', $tabletBrand->device_type);
        $this->assertSame('mobile', $mobileBrand->refresh()->device_type);
        $this->assertDatabaseHas('phone_series', ['id' => $tablet->phone_series_id, 'brand_id' => $tabletBrand->id, 'slug' => $series->slug]);
        $this->assertModelExists($series);
        $this->assertDatabaseHas('listings', ['id' => $listingId, 'brand_id' => $tabletBrand->id, 'phone_model_id' => $tablet->id]);

        $this->actingAs($admin)->delete(route('admin.brands.destroy', $tabletBrand))
            ->assertRedirect()->assertSessionHasErrors('brand');
        $this->assertModelExists($tabletBrand);
        $this->assertModelExists($tablet);
    }

    public function test_regular_user_cannot_toggle_catalog_items(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $brand = Brand::firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('admin.brands.toggle', $brand), [])
            ->assertForbidden();
    }
}
