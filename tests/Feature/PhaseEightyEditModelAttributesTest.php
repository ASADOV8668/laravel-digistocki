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

class PhaseEightyEditModelAttributesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_edit_form_only_exposes_active_attributes_attached_to_the_listing_model(): void
    {
        $owner = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail();
        Attribute::create([
            'name' => 'ویژگی خارج از مدل',
            'type' => 'string',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تست ویژگی مدل',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->get(route('listings.edit', $listing))
            ->assertOk()
            ->assertSee('حافظه داخلی')
            ->assertDontSee('ویژگی خارج از مدل');
    }
}
