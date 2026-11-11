<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandListingWizardStep;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandListingWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_brand_wizard_returns_series_models_and_attributes(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();

        $response = $this->getJson(route('listings.wizard.config', $brand));

        $response->assertOk()
            ->assertJsonPath('brand.id', $brand->id)
            ->assertJsonCount($brand->phoneSeries()->count(), 'series')
            ->assertJsonPath('steps.0.type', 'series')
            ->assertJsonPath('steps.1.type', 'model');
    }

    public function test_model_selection_narrows_wizard_to_the_selected_series_and_loads_attribute_options(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::query()->where('brand_id', $brand->id)->where('name_en', 'iPhone 13 Pro Max')->firstOrFail();

        $response = $this->getJson(route('listings.wizard.config', $brand).'?'.http_build_query([
            'series_id' => $model->phone_series_id,
            'model_id' => $model->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('models.0.series_id', $model->phone_series_id)
            ->assertJsonFragment(['type' => 'attribute'])
            ->assertJsonFragment(['slug' => 'color']);

        $batteryStage = collect($response->json('steps'))
            ->first(fn (array $step): bool => data_get($step, 'attribute.slug') === 'battery_health');

        $this->assertNotNull($batteryStage);
        $this->assertSame('swiper', $batteryStage['display_type']);
    }

    public function test_admin_can_configure_a_brand_stage_and_disable_brand_for_new_listings(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();
        $attribute = \App\Models\Attribute::query()->where('slug', 'color')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.brands.wizard-steps.store', $brand), [
                'step_type' => 'attribute',
                'attribute_id' => $attribute->id,
                'display_type' => 'buttons',
                'is_required' => 1,
                'sort_order' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('brand_listing_wizard_steps', [
            'brand_id' => $brand->id,
            'attribute_id' => $attribute->id,
            'step_type' => 'attribute',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.brands.posting-toggle', $brand))
            ->assertRedirect();

        $this->assertFalse($brand->refresh()->can_post_listings);
        $this->getJson(route('listings.wizard.config', $brand))->assertOk();
    }
}
