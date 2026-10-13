<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseFiftyTwoSearchNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->createVisibleCatalogListings();
    }

    public function test_arabic_keyboard_variants_find_persian_catalog_names(): void
    {
        $this->getJson(route('listings.search.suggestions', ['q' => 'آيفون']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'آیفون 13 پرو']);

        $this->getJson(route('listings.search.suggestions', ['q' => 'آيفون ۱۳']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'آیفون 13 پرو']);
    }

    public function test_common_persian_letter_variants_find_catalog_names(): void
    {
        $this->getJson(route('listings.search.suggestions', ['q' => 'ایفون ۱۳']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'آیفون 13 پرو']);

        $this->getJson(route('listings.search.suggestions', ['q' => 'کلکسی S23']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'گلکسی S23']);
    }

    public function test_suggestions_exclude_rejected_expired_and_disabled_models(): void
    {
        $brand = Brand::where('name_en', 'Samsung')->firstOrFail();
        $disabledModel = PhoneModel::where('name_en', 'Galaxy S24')->firstOrFail();
        $expiredModel = PhoneModel::where('name_en', 'Galaxy S24 Plus')->firstOrFail();
        $seller = User::factory()->create();

        Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $disabledModel->id,
            'title' => 'آگهی غیرفعال',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'is_user_disabled' => true,
        ]);
        Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $expiredModel->id,
            'title' => 'آگهی منقضی',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->getJson(route('listings.search.suggestions', ['q' => 'سامسونگ']))->assertOk();

        $response->assertJsonFragment(['label' => 'گلکسی S23']);
        $response->assertJsonMissing(['label' => $disabledModel->name_fa]);
        $response->assertJsonMissing(['label' => $expiredModel->name_fa]);
    }

    private function createVisibleCatalogListings(): void
    {
        $seller = User::factory()->create();
        foreach ([
            ['Apple', 'iPhone 13 Pro'],
            ['Apple', 'iPhone 13 Pro Max'],
            ['Xiaomi', 'Redmi Note 12 Pro+'],
            ['Samsung', 'Galaxy S23'],
        ] as [$brandName, $modelName]) {
            $brand = Brand::where('name_en', $brandName)->firstOrFail();
            $model = PhoneModel::where('name_en', $modelName)->firstOrFail();
            Listing::create([
                'user_id' => $seller->id,
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => $model->name_fa,
                'slug' => Str::uuid(),
                'price' => 1000000,
                'status' => ListingStatus::Approved,
            ]);
        }
    }
}
