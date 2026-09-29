<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseNinetyOneModelRequiredMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_model_attributes_endpoint_uses_required_flag_from_the_model_pivot(): void
    {
        [$model, $attribute] = $this->modelWithGlobalRequiredButOptionalAttribute();

        $this->getJson(route('listings.models.attributes', [$model, 'all' => 1]))
            ->assertOk()
            ->assertJsonFragment(['id' => $attribute->id, 'name' => $attribute->name, 'is_required' => false]);
    }

    public function test_create_wizard_initial_payload_uses_model_requirement(): void
    {
        [$model] = $this->modelWithGlobalRequiredButOptionalAttribute();

        $this->actingAs(User::factory()->create())
            ->withSession(['_old_input' => ['phone_model_id' => $model->id]])
            ->get(route('listings.create'))
            ->assertOk()
            ->assertSee('\\u0022is_required\\u0022:false', false);
    }

    private function modelWithGlobalRequiredButOptionalAttribute(): array
    {
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $attribute = Attribute::create([
            'name' => 'ویژگی اختیاری مدل',
            'type' => 'string',
            'is_filterable' => false,
            'is_required' => true,
            'sort_order' => 99,
            'is_active' => true,
        ]);
        $model->attributes()->attach($attribute->id, ['is_required' => false, 'sort_order' => 99]);

        return [$model, $attribute];
    }
}
