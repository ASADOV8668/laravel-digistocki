<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $visibleResponse = $this->getJson(route('listings.models.attributes', $visibleModel));
        $hiddenResponse = $this->getJson(route('listings.models.attributes', $hiddenModel));

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
        $this->getJson(route('listings.models.attributes', $excludedModel))
            ->assertOk()
            ->assertJsonMissing(['id' => $attribute->id]);
        $this->getJson(route('listings.models.attributes', $allowedModel))
            ->assertOk()
            ->assertJsonFragment(['id' => $attribute->id]);

        $this->actingAs($admin)->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertSee('شرط نمایش')
            ->assertSee('نام برند یا مدل را جستجو کنید')
            ->assertSee('انتخاب مدل خاص');
    }
}
