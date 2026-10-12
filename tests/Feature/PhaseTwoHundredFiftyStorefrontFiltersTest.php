<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\PhoneModel;
use App\Models\SellerStore;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoHundredFiftyStorefrontFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_storefront_filter_page_excludes_location_filters_and_uses_store_price_bounds(): void
    {
        [$store, $brand, $model] = $this->storeContext('bounded-store');
        $this->listing($store->user, $brand, $model, 'آگهی ارزان', 12000000);
        $this->listing($store->user, $brand, $model, 'آگهی گران', 28000000);

        $response = $this->get(route('storefront.show', $store));

        $response->assertOk()
            ->assertSee('storefront-filters')
            ->assertSee('storefront-min-price')
            ->assertSee('فقط نمایش آگهی‌های فعال')
            ->assertDontSee('name="province_id"', false)
            ->assertDontSee('name="city_id"', false)
            ->assertViewHas('priceFloor', 12000000)
            ->assertViewHas('priceCeiling', 28000000);
    }

    public function test_storefront_models_endpoint_only_returns_models_used_by_that_store(): void
    {
        [$store, $brand, $model] = $this->storeContext('models-store');
        $otherModel = PhoneModel::query()->where('brand_id', $brand->id)->whereKeyNot($model->id)->firstOrFail();
        $outsideSeller = User::factory()->create();
        $outsideStore = $this->store($outsideSeller, 'outside-model-store');
        $this->listing($store->user, $brand, $model, 'مدل موجود', 15000000);
        $this->listing($outsideSeller, $brand, $otherModel, 'مدل خارج از فروشگاه', 15000000);

        $response = $this->getJson(route('storefront.models', $store, false).'?brand_id='.$brand->id);

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $model->id);
        $this->assertSame($outsideStore->user_id, $outsideSeller->id);
    }

    public function test_storefront_attribute_endpoint_only_returns_attributes_present_in_store_listings(): void
    {
        [$store, $brand, $model] = $this->storeContext('attributes-store');
        $listing = $this->listing($store->user, $brand, $model, 'با ویژگی فروشگاه', 16000000);
        $storeAttribute = Attribute::create(['name' => 'ویژگی اختصاصی فروشگاه', 'slug' => 'store-only-feature', 'type' => AttributeType::Select, 'options' => ['گزینه فروشگاه'], 'is_filterable' => true, 'is_active' => true]);
        $outsideAttribute = Attribute::create(['name' => 'ویژگی فروشگاه دیگر', 'slug' => 'outside-store-feature', 'type' => AttributeType::Select, 'options' => ['گزینه دیگر'], 'is_filterable' => true, 'is_active' => true]);
        ListingAttributeValue::create(['listing_id' => $listing->id, 'attribute_id' => $storeAttribute->id, 'value_string' => 'گزینه فروشگاه']);

        $response = $this->getJson(route('storefront.models.attributes', [$store, $model], false));

        $response->assertOk()->assertJsonFragment(['id' => $storeAttribute->id, 'name' => $storeAttribute->name])->assertJsonMissing(['id' => $outsideAttribute->id]);
    }

    public function test_storefront_filters_results_by_model_and_price_without_leaking_other_store_listings(): void
    {
        [$store, $brand, $model] = $this->storeContext('query-store');
        $matching = $this->listing($store->user, $brand, $model, 'آگهی مطابق فروشگاه', 18000000);
        $this->listing($store->user, $brand, $model, 'آگهی گران فروشگاه', 38000000);
        $outsideSeller = User::factory()->create();
        $this->listing($outsideSeller, $brand, $model, 'آگهی مطابق فروشنده دیگر', 18000000);

        $response = $this->get(route('storefront.show', [
            'sellerStore' => $store->slug,
            'phone_model_id' => $model->id,
            'min_price' => 17000000,
            'max_price' => 19000000,
        ]));

        $response->assertOk();
        $titles = $response->viewData('listings')->getCollection()->pluck('title')->all();
        $this->assertSame([$matching->title], $titles);
    }

    public function test_storefront_hides_price_filter_when_all_store_listings_are_contact_price(): void
    {
        [$store, $brand, $model] = $this->storeContext('contact-store');
        $this->listing($store->user, $brand, $model, 'قیمت توافقی', null, true);

        $response = $this->get(route('storefront.show', $store));

        $response->assertOk()->assertViewHas('hasPricedListings', false)->assertDontSee('storefront-min-price');
    }

    public function test_storefront_hides_price_filter_when_all_prices_are_identical(): void
    {
        [$store, $brand, $model] = $this->storeContext('same-price-store');
        $this->listing($store->user, $brand, $model, 'قیمت یکسان اول', 22000000);
        $this->listing($store->user, $brand, $model, 'قیمت یکسان دوم', 22000000);

        $response = $this->get(route('storefront.show', $store));

        $response->assertOk()->assertViewHas('hasPriceRange', false)->assertDontSee('storefront-min-price');
    }

    public function test_authenticated_visitors_can_reveal_store_contact_details(): void
    {
        [$store, $brand, $model] = $this->storeContext('contact-details-store');
        $store->update([
            'contact_phone' => '09120000000',
            'address' => 'تهران، خیابان تست',
            'instagram_url' => 'store_test',
        ]);
        $this->listing($store->user, $brand, $model, 'آگهی اطلاعات تماس', 22000000);

        $this->actingAs(User::factory()->create())
            ->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee('اطلاعات تماس فروشگاه')
            ->assertSee('09120000000')
            ->assertSee('تهران، خیابان تست')
            ->assertSee('instagram.com/store_test');
    }

    private function storeContext(string $slug): array
    {
        $seller = User::factory()->create();
        $store = $this->store($seller, $slug);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        return [$store, $brand, $model];
    }

    private function store(User $seller, string $slug): SellerStore
    {
        return SellerStore::create(['user_id' => $seller->id, 'slug' => $slug, 'name' => 'فروشگاه '.$slug, 'is_enabled' => true, 'approval_status' => 'approved']);
    }

    private function listing(User $seller, Brand $brand, PhoneModel $model, string $title, ?int $price, bool $contact = false): Listing
    {
        return Listing::create([
            'user_id' => $seller->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id,
            'title' => $title, 'slug' => Str::uuid(), 'price' => $contact ? null : $price,
            'price_on_request' => $contact, 'status' => ListingStatus::Approved, 'published_at' => now(),
        ]);
    }
}
