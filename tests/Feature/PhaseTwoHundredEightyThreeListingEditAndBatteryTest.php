<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseTwoHundredEightyThreeListingEditAndBatteryTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_battery_health_is_seeded_with_fixed_choices_and_rendered_as_a_select(): void
    {
        $options = array_values(array_unique(array_merge(range(0, 50, 10), range(55, 100, 5))));
        $battery = Attribute::query()->where('slug', 'battery_health')->firstOrFail();

        $this->assertSame($options, $battery->options);

        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی انتخاب سلامت باتری',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Pending,
        ]);

        $this->actingAs($admin)->get(route('admin.listings.edit', $listing))
            ->assertOk()
            ->assertSee('isBatteryHealth(row.attributeId)', false)
            ->assertSee('"slug":"battery_health"', false)
            ->assertSee('"options":[0,10,20,30,40,50,55,60,65,70,75,80,85,90,95,100]', false);
    }

    public function test_battery_health_rejects_values_outside_the_fixed_choices(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی سلامت باتری',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Rejected,
        ]);
        $battery = Attribute::query()->where('slug', 'battery_health')->firstOrFail();
        $attributes = $this->requiredAttributeValues($model);
        $attributes[$battery->id] = 53;

        $this->actingAs($user)
            ->put(route('listings.update', $listing), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی سلامت باتری ویرایش‌شده',
                'price' => 2000000,
                'attributes' => $attributes,
            ])
            ->assertSessionHasErrors('attributes.'.$battery->id);
    }

    public function test_user_edit_form_has_no_nested_delete_form_and_keeps_the_submit_form(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی فرم ویرایش',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Rejected,
        ]);
        $image = ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/form-edit.webp',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)->get(route('listings.edit', $listing))->assertOk();
        $html = $response->getContent();
        $updateFormEnd = strpos($html, '</form>');
        $deleteAction = strpos($html, route('listings.images.destroy', [$listing, $image]));

        $this->assertNotFalse($updateFormEnd);
        $this->assertStringContainsString('ذخیره و', $html);
        $this->assertNotFalse($deleteAction);
        $this->assertGreaterThan($updateFormEnd, $deleteAction);
    }
}
