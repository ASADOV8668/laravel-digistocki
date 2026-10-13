<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\SellerStore;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseTwoHundredFiftyOneUserListingDefaultsTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
    }

    public function test_user_listing_form_uses_store_location_and_generates_title_without_formatted_price(): void
    {
        [$province, $city] = $this->location();
        $user = User::factory()->create();
        SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'defaults-store-'.$user->id,
            'name' => 'فروشگاه پیش‌فرض',
            'province_id' => $province->id,
            'city_id' => $city->id,
        ]);
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->where('name', 'iPhone 13 Pro')->firstOrFail();
        $color = Attribute::where('slug', 'color')->firstOrFail();
        $storage = Attribute::where('slug', 'storage')->firstOrFail();
        $attributes = $this->requiredAttributeValues($model);
        $attributes[$color->id] = 'آبی';
        $attributes[$storage->id] = 256;

        $this->actingAs($user)
            ->get(route('listings.create'))
            ->assertOk()
            ->assertSee('data-initial-province-id="'.$province->id.'"', false)
            ->assertSee('data-initial-city-id="'.$city->id.'"', false)
            ->assertSee('moneyInput', false)
            ->assertSee('data-auto-listing-title', false);

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'price' => '32000000',
            'attributes' => $attributes,
            'images' => [UploadedFile::fake()->create('phone.jpg', 100, 'image/jpeg')],
        ])->assertRedirect();

        $listing = Listing::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($province->id, $listing->province_id);
        $this->assertSame($city->id, $listing->city_id);
        $this->assertSame(32000000, $listing->price);
        $this->assertStringContainsString('اپل', $listing->title);
        $this->assertStringContainsString('آیفون ۱۳ پرو', $listing->title);
        $this->assertStringContainsString('آبی', $listing->title);
        $this->assertStringContainsString('۲۵۶ گیگابایت', $listing->title);
    }

    public function test_user_cannot_create_a_listing_without_an_image(): void
    {
        [$province, $city] = $this->location();
        $user = User::factory()->create();
        SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'required-image-store-'.$user->id,
            'name' => 'فروشگاه تصویر',
            'province_id' => $province->id,
            'city_id' => $city->id,
        ]);
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)
            ->post(route('listings.store'), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی بدون تصویر',
                'price' => 1000000,
                'attributes' => $this->requiredAttributeValues($model),
            ])
            ->assertSessionHasErrors(['images' => 'حداقل یک تصویر برای آگهی انتخاب کنید.']);
    }

    /** @return array{0: Province, 1: City} */
    private function location(): array
    {
        $province = Province::create(['name' => 'استان پیش‌فرض', 'tel_prefix' => '99']);
        $county = County::create(['name' => 'شهرستان پیش‌فرض', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر پیش‌فرض', 'province_id' => $province->id, 'county_id' => $county->id]);

        return [$province, $city];
    }
}
