<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseEightyEightEditRequirementDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_edit_form_reads_required_marker_from_model_attribute_pivot(): void
    {
        $owner = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::create([
            'brand_id' => $brand->id,
            'name' => 'Pivot Requirement Test',
            'name_fa' => 'مدل تست الزام',
            'name_en' => 'Pivot Requirement Test',
            'slug' => 'pivot-requirement-test',
            'is_active' => true,
        ]);
        $attribute = Attribute::create([
            'name' => 'ویژگی اختیاری مدل',
            'type' => 'string',
            'is_required' => true,
            'is_active' => true,
        ]);
        $model->modelAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_required' => false,
        ]);
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تست الزام مدل',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('listings.edit', $listing))
            ->assertOk()
            ->assertSee('ویژگی اختیاری مدل')
            ->assertDontSee('ویژگی اختیاری مدل <span class="text-error">*</span>', false);
    }
}
