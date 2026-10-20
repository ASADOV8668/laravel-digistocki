<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeOptionRestriction;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeOptionRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_create_and_delete_model_option_restriction_and_listing_payload_uses_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attribute = Attribute::query()->where('slug', 'color')->firstOrFail();
        $models = PhoneModel::query()->take(3)->get();
        $restrictedModels = $models->take(2);
        $otherModel = $models->last();
        $allowedOptions = array_slice($attribute->options, 0, 2);

        $this->actingAs($admin)->post(route('admin.attributes.option-restrictions.store', $attribute), [
            'phone_model_ids' => $restrictedModels->pluck('id')->all(),
            'options' => $allowedOptions,
        ])->assertRedirect();

        $rule = AttributeOptionRestriction::query()->where('attribute_id', $attribute->id)->firstOrFail();
        $this->assertSame($allowedOptions, $rule->options);

        $restrictedResponse = $this->getJson(route('listings.models.attributes', $restrictedModels->first()).'?all=1');
        $restrictedResponse->assertOk();
        $this->assertSame($allowedOptions, collect($restrictedResponse->json())->firstWhere('id', $attribute->id)['options']);

        $unrestrictedResponse = $this->getJson(route('listings.models.attributes', $otherModel).'?all=1');
        $unrestrictedResponse->assertOk();
        $this->assertSame($attribute->options, collect($unrestrictedResponse->json())->firstWhere('id', $attribute->id)['options']);

        $this->actingAs($admin)->get(route('admin.attributes.option-restrictions.index', $attribute))
            ->assertOk()
            ->assertSee('محدودسازی گزینه‌های '.$attribute->name)
            ->assertSee($allowedOptions[0]);

        $this->actingAs($admin)->delete(route('admin.attributes.option-restrictions.destroy', [$attribute, $rule]))
            ->assertRedirect();
        $this->assertDatabaseMissing('attribute_option_restrictions', ['id' => $rule->id]);
    }
}
