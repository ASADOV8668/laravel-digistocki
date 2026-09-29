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

class PhaseSeventyEightEditAttributeInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_edit_form_uses_input_types_that_match_dynamic_attribute_types(): void
    {
        $owner = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail();
        $text = Attribute::create(['name' => 'توضیحات ظاهری', 'type' => 'string', 'is_active' => true, 'sort_order' => 98]);
        $decimal = Attribute::create(['name' => 'وزن دقیق', 'type' => 'decimal', 'is_active' => true, 'sort_order' => 99]);
        $model->modelAttributes()->createMany([
            ['attribute_id' => $text->id, 'sort_order' => 98],
            ['attribute_id' => $decimal->id, 'sort_order' => 99],
        ]);
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تست ورودی‌ها',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('listings.edit', $listing))
            ->assertOk()
            ->assertSee('type="text"', false)
            ->assertSee('step="0.01"', false);
    }
}
