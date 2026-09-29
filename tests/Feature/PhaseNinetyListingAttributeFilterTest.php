<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\ListingStatus;
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

class PhaseNinetyListingAttributeFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_multi_select_filters_match_all_selected_json_values(): void
    {
        [$model, $attribute] = $this->multiSelectAttribute();
        $matching = $this->listing('آگهی قرمز و آبی', $model);
        $redOnly = $this->listing('آگهی قرمز فقط', $model);

        ListingAttributeValue::create(['listing_id' => $matching->id, 'attribute_id' => $attribute->id, 'value_json' => ['قرمز', 'آبی']]);
        ListingAttributeValue::create(['listing_id' => $redOnly->id, 'attribute_id' => $attribute->id, 'value_json' => ['قرمز']]);

        $response = $this->get(route('listings.index', [
            'phone_model_id' => $model->id,
            'filters' => [$attribute->id => ['قرمز', 'آبی']],
        ]));

        $response->assertOk()->assertSee('multiple', false);
        $titles = $response->viewData('listings')->getCollection()->pluck('title')->all();
        $this->assertSame(['آگهی قرمز و آبی'], $titles);
    }

    public function test_numeric_filter_uses_the_attribute_storage_column(): void
    {
        $brand = Brand::firstOrFail();
        $model = PhoneModel::where('brand_id', $brand->id)->firstOrFail();
        $attribute = Attribute::create([
            'name' => 'حافظه تست',
            'type' => AttributeType::Integer,
            'is_filterable' => true,
            'is_required' => false,
            'sort_order' => 99,
            'is_active' => true,
        ]);
        $model->attributes()->attach($attribute->id, ['is_required' => false, 'sort_order' => 99]);
        $matching = $this->listing('آگهی ۲۵۶ گیگ', $model);
        $other = $this->listing('آگهی ۱۲۸ گیگ', $model);
        ListingAttributeValue::create(['listing_id' => $matching->id, 'attribute_id' => $attribute->id, 'value_integer' => 256]);
        ListingAttributeValue::create(['listing_id' => $other->id, 'attribute_id' => $attribute->id, 'value_integer' => 128]);

        $response = $this->get(route('listings.index', [
            'phone_model_id' => $model->id,
            'filters' => [$attribute->id => '256'],
        ]));

        $titles = $response->viewData('listings')->getCollection()->pluck('title')->all();
        $this->assertSame(['آگهی ۲۵۶ گیگ'], $titles);
    }

    private function multiSelectAttribute(): array
    {
        $brand = Brand::firstOrFail();
        $model = PhoneModel::where('brand_id', $brand->id)->firstOrFail();
        $attribute = Attribute::create([
            'name' => 'رنگ‌های تست',
            'type' => AttributeType::MultiSelect,
            'options' => ['قرمز', 'آبی', 'سبز'],
            'is_filterable' => true,
            'is_required' => false,
            'sort_order' => 99,
            'is_active' => true,
        ]);
        $model->attributes()->attach($attribute->id, ['is_required' => false, 'sort_order' => 99]);

        return [$model, $attribute];
    }

    private function listing(string $title, PhoneModel $model): Listing
    {
        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $model->brand_id,
            'phone_model_id' => $model->id,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
