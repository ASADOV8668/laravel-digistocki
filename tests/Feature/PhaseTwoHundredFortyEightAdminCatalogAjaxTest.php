<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_regular_user_cannot_toggle_catalog_items(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $brand = Brand::firstOrFail();

        $this->actingAs($user)
            ->patchJson(route('admin.brands.toggle', $brand), [])
            ->assertForbidden();
    }
}
