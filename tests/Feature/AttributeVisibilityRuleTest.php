<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\PhoneModel;
use App\Models\SellerStore;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttributeVisibilityRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_save_a_specific_model_visibility_rule_and_it_controls_listing_attributes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $models = PhoneModel::query()->take(2)->get();
        $visibleModel = $models->firstOrFail();
        $hiddenModel = $models->last();

        $response = $this->actingAs($admin)->post(route('admin.attributes.store'), [
            'name' => 'ویژگی مخصوص مدل',
            'type' => AttributeType::Select->value,
            'options' => 'گزینه اول, گزینه دوم',
            'is_filterable' => true,
            'visibility_rule' => 'include',
            'visibility_model_ids' => [$visibleModel->id],
        ]);

        $response->assertRedirect();
        $attribute = Attribute::query()->where('slug', null)->latest('id')->firstOrFail();
        $this->assertSame('include', $attribute->visibility_rule);
        $this->assertSame([$visibleModel->id], $attribute->visibility_model_ids);
        $this->assertDatabaseHas('model_attributes', ['phone_model_id' => $visibleModel->id, 'attribute_id' => $attribute->id]);

        $visibleResponse = $this->getJson(route('listings.models.attributes', [$visibleModel, 'all' => 1]));
        $hiddenResponse = $this->getJson(route('listings.models.attributes', [$hiddenModel, 'all' => 1]));

        $visibleResponse->assertOk()->assertJsonFragment(['id' => $attribute->id, 'name' => 'ویژگی مخصوص مدل']);
        $hiddenResponse->assertOk()->assertJsonMissing(['id' => $attribute->id]);
    }

    public function test_admin_can_save_excluded_models_and_admin_form_exposes_searchable_condition_picker(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $models = PhoneModel::query()->take(2)->get();
        $excludedModel = $models->firstOrFail();
        $allowedModel = $models->last();
        $attribute = Attribute::query()->where('slug', 'color')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.attributes.update', $attribute), [
            'name' => $attribute->name,
            'type' => $attribute->type->value,
            'options' => implode(',', $attribute->options ?? []),
            'is_filterable' => true,
            'is_required' => $attribute->is_required,
            'visibility_rule' => 'exclude',
            'visibility_model_ids' => [$excludedModel->id],
        ])->assertRedirect();

        $this->assertSame('exclude', $attribute->refresh()->visibility_rule);
        $this->getJson(route('listings.models.attributes', [$excludedModel, 'all' => 1]))
            ->assertOk()
            ->assertJsonMissing(['id' => $attribute->id]);
        $this->getJson(route('listings.models.attributes', [$allowedModel, 'all' => 1]))
            ->assertOk()
            ->assertJsonFragment(['id' => $attribute->id]);

        $this->actingAs($admin)->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertSee('شرط نمایش')
            ->assertSee('نام برند یا مدل را جستجو کنید')
            ->assertSee('انتخاب مدل خاص')
            ->assertDontSee('attributeVisibilityPicker([');

        $this->actingAs($admin)->getJson(route('admin.attributes.models', ['q' => $allowedModel->name]))
            ->assertOk()
            ->assertJsonFragment(['id' => $allowedModel->id]);
    }

    public function test_public_filters_keep_historical_attributes_even_if_visibility_rule_changed_later(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'visibility-store',
            'name' => 'فروشگاه شرط نمایش',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);
        $models = PhoneModel::query()->take(2)->get();
        $visibleModel = $models->firstOrFail();
        $hiddenModel = $models->last();
        $attribute = Attribute::query()->where('slug', 'color')->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.attributes.update', $attribute), [
            'name' => $attribute->name,
            'type' => $attribute->type->value,
            'options' => implode(',', $attribute->options ?? []),
            'is_filterable' => true,
            'is_required' => false,
            'visibility_rule' => 'include',
            'visibility_model_ids' => [$visibleModel->id],
        ])->assertRedirect();

        foreach ([$visibleModel, $hiddenModel] as $model) {
            $listing = Listing::create([
                'user_id' => $seller->id,
                'brand_id' => $model->brand_id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی '.$model->id,
                'slug' => Str::uuid(),
                'price' => 1000000,
                'status' => ListingStatus::Approved,
                'published_at' => now(),
            ]);
            ListingAttributeValue::create([
                'listing_id' => $listing->id,
                'attribute_id' => $attribute->id,
                'value_string' => $attribute->options[0],
            ]);
        }

        $this->getJson(route('storefront.models.attributes', [
            'sellerStore' => $store,
            'phoneModel' => $visibleModel,
        ]))->assertOk()->assertJsonFragment(['id' => $attribute->id]);

        $this->getJson(route('storefront.models.attributes', [
            'sellerStore' => $store,
            'phoneModel' => $hiddenModel,
        ]))->assertOk()->assertJsonFragment(['id' => $attribute->id]);

        $this->getJson(route('listings.models.attributes', $hiddenModel))
            ->assertOk()
            ->assertJsonFragment(['id' => $attribute->id]);
    }
}
