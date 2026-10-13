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

class PhaseOneHundredSeventyFiveAjaxListingCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_models_endpoint_returns_bilingual_models_and_filters_by_brand(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $otherBrand = Brand::where('name_en', 'Samsung')->firstOrFail();
        $seller = User::factory()->create();
        foreach (['iPhone 11', 'iPhone 13 Pro'] as $modelName) {
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

        $this->getJson(route('listings.models', ['brand_id' => $brand->id]))
            ->assertOk()
            ->assertJsonFragment(['brand_id' => $brand->id, 'label' => 'آیفون 11', 'secondary' => 'iPhone 11'])
            ->assertJsonMissing(['brand_id' => $otherBrand->id]);

        $this->getJson(route('listings.models', ['q' => 'iPhone 13']))
            ->assertOk()
            ->assertJsonFragment(['secondary' => 'iPhone 13']);

        $disabled = PhoneModel::where('name_en', 'iPhone 14')->firstOrFail();
        Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $disabled->id,
            'title' => 'مدل غیرفعال',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'is_user_disabled' => true,
        ]);
        $this->getJson(route('listings.models', ['brand_id' => $brand->id, 'scope' => 'search']))
            ->assertOk()
            ->assertJsonMissing(['id' => $disabled->id]);
    }

    public function test_listing_page_uses_ajax_model_catalog_and_dynamic_attributes(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->get(route('listings.index'))->assertOk();

        $view = file_get_contents(resource_path('views/listings/index.blade.php'));
        $this->assertNotFalse($view);
        $this->assertStringContainsString('url("/listings/models")', $view);

        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertNotFalse($script);
        $this->assertStringContainsString('modelsEndpoint', $script);
        $this->assertStringContainsString('loadModels', $script);
        $this->assertStringContainsString('selectBrandId', $script);
        $this->assertStringContainsString('modelsLoading', $script);
    }
}
