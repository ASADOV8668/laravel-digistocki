<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandListingWizardStep;
use App\Models\Attribute;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\PhoneModel;
use App\Models\SellerStore;
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

        $this->assertSame(
            ['Apple', 'Samsung', 'Xiaomi'],
            Brand::query()->where('can_post_listings', true)->orderBy('name_en')->pluck('name_en')->all(),
        );

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

    public function test_edit_form_exposes_existing_brand_series_model_and_attribute_values(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'edit-wizard-store-'.$user->id,
            'name' => 'فروشگاه ویرایش سریع',
            'approval_status' => 'approved',
            'is_enabled' => true,
            'listing_approval_mode' => SellerStore::LISTING_APPROVAL_FREE,
        ]);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::query()->where('brand_id', $brand->id)->where('name_en', 'iPhone 13 Pro Max')->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی ویرایش سریع',
            'slug' => 'edit-wizard-'.$user->id,
            'price' => 1000000,
            'status' => 'approved',
            'published_at' => now(),
        ]);

        foreach ([
            ['storage', 'value_integer', 256],
            ['ram', 'value_integer', 6],
            ['color', 'value_string', 'آبی'],
            ['condition', 'value_string', 'در حد نو'],
            ['battery_health', 'value_integer', 90],
        ] as [$slug, $field, $value]) {
            ListingAttributeValue::create([
                'listing_id' => $listing->id,
                'attribute_id' => Attribute::query()->where('slug', $slug)->value('id'),
                $field => $value,
            ]);
        }

        $response = $this->actingAs($user)->get(route('listings.edit', $listing))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('data-initial-brand-id="'.$brand->id.'"', $html);
        $this->assertStringContainsString('data-initial-series-id="'.$model->phone_series_id.'"', $html);
        $this->assertStringContainsString('data-initial-model-id="'.$model->id.'"', $html);
        $this->assertStringContainsString('"'.Attribute::query()->where('slug', 'storage')->value('id').'":256', $html);
        $this->assertStringContainsString('"'.Attribute::query()->where('slug', 'battery_health')->value('id').'":90', $html);
    }
}
