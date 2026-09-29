<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseEightySevenModelAttributeRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_mark_a_model_attribute_required_and_listing_validation_honors_the_pivot(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail();
        $attribute = Attribute::where('name', 'جعبه و لوازم')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.phone-models.update', $model), [
                'brand_id' => $brand->id,
                'name' => $model->name,
                'name_fa' => $model->name_fa,
                'name_en' => $model->name_en,
                'attribute_ids' => [$attribute->id],
                'required_attribute_ids' => [$attribute->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('model_attributes', [
            'phone_model_id' => $model->id,
            'attribute_id' => $attribute->id,
            'is_required' => 1,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('listings.store'), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی با ویژگی اجباری مدل',
                'price' => 1000000,
                'attributes' => [],
            ])
            ->assertSessionHasErrors(['attributes.'.$attribute->id]);
    }
}
