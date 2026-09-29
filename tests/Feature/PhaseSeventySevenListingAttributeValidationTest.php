<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSeventySevenListingAttributeValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_store_rejects_values_that_are_not_valid_options_for_the_model(): void
    {
        $user = User::factory()->create();
        [$brand, $model] = $this->catalogContext();
        $color = Attribute::where('name', 'رنگ')->firstOrFail();

        $this->actingAs($user)
            ->post(route('listings.store'), $this->listingPayload($brand, $model, [
                $color->id => 'رنگ نامعتبر',
            ]))
            ->assertSessionHasErrors(['attributes.'.$color->id]);

        $this->assertDatabaseMissing('listings', ['title' => 'آگهی گزینه نامعتبر']);
    }

    public function test_update_rejects_values_that_are_not_allowed_by_the_dynamic_attribute_definition(): void
    {
        $user = User::factory()->create();
        [$brand, $model] = $this->catalogContext();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی قابل ویرایش',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Pending,
        ]);
        $memory = Attribute::where('name', 'حافظه داخلی')->firstOrFail();

        $this->actingAs($user)
            ->put(route('listings.update', $listing), $this->listingPayload($brand, $model, [
                $memory->id => '256.5',
            ], 'آگهی ویرایش نامعتبر'))
            ->assertSessionHasErrors(['attributes.'.$memory->id]);

        $this->assertSame('آگهی قابل ویرایش', $listing->refresh()->title);
    }

    public function test_valid_multi_select_values_are_saved_as_json(): void
    {
        $user = User::factory()->create();
        [$brand, $model] = $this->catalogContext();
        $attribute = Attribute::create([
            'name' => 'ویژگی چندانتخابی تست',
            'type' => 'multi_select',
            'options' => ['قرمز', 'آبی'],
            'is_active' => true,
            'sort_order' => 99,
        ]);
        $model->modelAttributes()->create(['attribute_id' => $attribute->id, 'sort_order' => 99]);

        $this->actingAs($user)
            ->post(route('listings.store'), $this->listingPayload($brand, $model, [
                $attribute->id => ['قرمز', 'آبی'],
            ], 'آگهی چندانتخابی معتبر'))
            ->assertRedirect();

        $listing = Listing::where('title', 'آگهی چندانتخابی معتبر')->firstOrFail();
        $this->assertSame(['قرمز', 'آبی'], $listing->attributeValues()->where('attribute_id', $attribute->id)->firstOrFail()->value_json);
    }

    private function catalogContext(): array
    {
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();

        return [$brand, PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail()];
    }

    private function listingPayload(Brand $brand, PhoneModel $model, array $attributes, string $title = 'آگهی گزینه نامعتبر'): array
    {
        return [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => $title,
            'price' => 32000000,
            'attributes' => $attributes,
        ];
    }
}
