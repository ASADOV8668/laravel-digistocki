<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSeventySixMultiSelectAttributesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_single_listing_displays_multi_select_values_as_a_readable_list(): void
    {
        [$listing, $attribute] = $this->listingWithMultiSelectValue('approved');

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee($attribute->name)
            ->assertSee('قرمز، آبی');
    }

    public function test_edit_form_exposes_multi_select_as_multiple_control_with_saved_values(): void
    {
        $owner = User::factory()->create();
        [$listing, $attribute] = $this->listingWithMultiSelectValue('pending', $owner);

        $this->actingAs($owner)
            ->get(route('listings.edit', $listing))
            ->assertOk()
            ->assertSee('multiple', false)
            ->assertSee('attributes['.$attribute->id.'][]', false)
            ->assertSee('value="قرمز" selected', false)
            ->assertSee('value="آبی" selected', false);
    }

    /** @return array{0: Listing, 1: Attribute} */
    private function listingWithMultiSelectValue(string $status, ?User $owner = null): array
    {
        $owner ??= User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail();
        $attribute = Attribute::create([
            'name' => 'رنگ‌های انتخابی',
            'type' => 'multi_select',
            'options' => ['قرمز', 'آبی', 'سبز'],
            'is_filterable' => true,
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 99,
        ]);
        $model->modelAttributes()->create([
            'attribute_id' => $attribute->id,
            'is_required' => false,
            'sort_order' => 99,
        ]);

        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تست آیفون',
            'slug' => Str::slug('آگهی تست آیفون').'-'.Str::lower(Str::random(8)),
            'description' => 'توضیحات تست',
            'price' => 10000000,
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);
        ListingAttributeValue::create([
            'listing_id' => $listing->id,
            'attribute_id' => $attribute->id,
            'value_json' => ['قرمز', 'آبی'],
        ]);

        return [$listing, $attribute];
    }
}
