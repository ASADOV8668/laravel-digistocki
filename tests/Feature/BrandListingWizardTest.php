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

        $this->assertEqualsCanonicalizing(
            ['X', '17', '18', 'Duo'],
            $brand->phoneSeries()->whereIn('name_en', ['X', '17', '18', 'Duo'])->pluck('name_en')->all(),
        );
        $this->assertDatabaseHas('phone_models', ['brand_id' => $brand->id, 'name_en' => 'iPhone Duo']);

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

        $this->assertNull(collect($response->json('steps'))
            ->first(fn (array $step): bool => data_get($step, 'attribute.slug') === 'ram'));
        $this->assertNull(collect($response->json('steps'))
            ->first(fn (array $step): bool => data_get($step, 'attribute.slug') === 'infrared'));
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

    public function test_camera_catalog_filters_device_type_and_series_and_orders_model_choices(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();
        $mobile = $brand->phoneModels()->where('name_en', 'iPhone 13 Pro Max')->firstOrFail();
        $series = $brand->phoneSeries()->create(['name' => 'Test tablet series', 'slug' => 'test-tablet-series', 'is_active' => true]);
        $tablet = $brand->phoneModels()->create([
            'name' => 'Test tablet',
            'slug' => 'test-tablet',
            'phone_series_id' => $series->id,
            'device_type' => 'tablet',
            'is_active' => true,
        ]);
        $withoutSeries = $brand->phoneModels()->create([
            'name' => 'Test tablet without series',
            'slug' => 'test-tablet-without-series',
            'device_type' => 'tablet',
            'is_active' => true,
        ]);
        foreach (['storage' => ['128', '256'], 'color' => ['آبی']] as $slug => $options) {
            $attribute = Attribute::where('slug', $slug)->firstOrFail();
            $tablet->attributes()->attach($attribute->id, ['is_required' => true]);
            $attribute->optionRestrictions()->create(['phone_model_ids' => [$tablet->id], 'options' => $options]);
        }
        $inactive = Attribute::create(['slug' => 'inactive-camera-feature', 'name' => 'Inactive camera feature', 'type' => 'string', 'is_active' => false]);
        $tablet->attributes()->attach($inactive->id, ['is_required' => true]);

        $this->getJson(route('listings.wizard.config', [$brand, 'device_type' => 'tablet', 'series_id' => $series->id, 'model_id' => $tablet->id]))
            ->assertOk()
            ->assertJsonCount(2, 'series')
            ->assertJsonPath('series.0.id', $series->id)
            ->assertJsonPath('series.1.id', 'none')
            ->assertJsonCount(1, 'models')
            ->assertJsonPath('models.0.id', $tablet->id)
            ->assertJsonPath('steps.0.type', 'series')
            ->assertJsonPath('steps.1.type', 'model')
            ->assertJsonPath('steps.2.attribute.slug', 'storage')
            ->assertJsonPath('steps.2.attribute.options', ['128', '256'])
            ->assertJsonPath('steps.3.attribute.slug', 'color')
            ->assertJsonPath('steps.3.attribute.options', ['آبی'])
            ->assertJsonMissing(['slug' => 'inactive-camera-feature']);

        $this->getJson(route('listings.wizard.config', [$brand, 'device_type' => 'tablet', 'series_id' => $mobile->phone_series_id]))
            ->assertOk()
            ->assertJsonCount(0, 'models');
        $this->getJson(route('listings.wizard.config', [$brand, 'device_type' => 'tablet', 'series_id' => 'none']))
            ->assertOk()
            ->assertJsonCount(1, 'models')
            ->assertJsonPath('models.0.id', $withoutSeries->id);
    }

    public function test_admin_stage_builder_shows_default_brand_stages_when_no_custom_steps_exist(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.brands.wizard-steps.index', $brand))
            ->assertOk()
            ->assertSee('مراحل پیش‌فرض فعال')
            ->assertSee('انتخاب سری')
            ->assertSee('انتخاب مدل')
            ->assertSee('حافظه داخلی')
            ->assertSee('پیش‌فرض');
    }

    public function test_admin_can_materialize_reorder_and_delete_brand_wizard_stages(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.brands.wizard-steps.materialize', $brand))
            ->assertRedirect();

        $steps = $brand->wizardSteps()->orderBy('sort_order')->get();
        $this->assertGreaterThan(2, $steps->count());

        $this->actingAs($admin)
            ->get(route('admin.brands.wizard-steps.index', $brand))
            ->assertOk()
            ->assertSee('data-wizard-step-row', false)
            ->assertSee('ذخیره ترتیب مراحل');

        $reorderedIds = $steps->pluck('id')->reverse()->values();
        $sortOrders = $reorderedIds->mapWithKeys(
            fn (int $stepId, int $index): array => [$stepId => $index + 1],
        )->all();

        $this->actingAs($admin)
            ->patch(route('admin.brands.wizard-steps.reorder', $brand), [
                'sort_orders' => $sortOrders,
            ])
            ->assertRedirect();

        $this->assertSame(
            $reorderedIds->all(),
            $brand->wizardSteps()->orderBy('sort_order')->pluck('id')->values()->all(),
        );

        $deletedStep = $brand->wizardSteps()->firstOrFail();
        $this->actingAs($admin)
            ->delete(route('admin.brands.wizard-steps.destroy', [$brand, $deletedStep]))
            ->assertRedirect();

        $this->assertDatabaseMissing('brand_listing_wizard_steps', ['id' => $deletedStep->id]);
    }

    public function test_brand_stage_attributes_are_limited_to_attributes_selected_for_the_model(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::query()->where('brand_id', $brand->id)->where('name_en', 'iPhone 13 Pro Max')->firstOrFail();
        $ram = Attribute::query()->where('slug', 'ram')->firstOrFail();
        $color = Attribute::query()->where('slug', 'color')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.brands.wizard-steps.store', $brand), [
                'step_type' => 'attribute',
                'attribute_id' => $ram->id,
                'display_type' => 'buttons',
                'is_required' => 0,
                'sort_order' => 3,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.brands.wizard-steps.store', $brand), [
                'step_type' => 'attribute',
                'attribute_id' => $color->id,
                'display_type' => 'buttons',
                'is_required' => 1,
                'sort_order' => 4,
            ])
            ->assertRedirect();

        $this->getJson(route('listings.wizard.config', [$brand, 'model_id' => $model->id]))
            ->assertOk()
            ->assertJsonMissing(['slug' => 'ram'])
            ->assertJsonFragment(['slug' => 'color']);
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
