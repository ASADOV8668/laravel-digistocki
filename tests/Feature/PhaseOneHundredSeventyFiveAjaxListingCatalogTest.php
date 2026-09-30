<?php

namespace Tests\Feature;

use App\Models\Brand;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSeventyFiveAjaxListingCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_models_endpoint_returns_bilingual_models_and_filters_by_brand(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $otherBrand = Brand::where('name_en', 'Samsung')->firstOrFail();

        $this->getJson(route('listings.models', ['brand_id' => $brand->id]))
            ->assertOk()
            ->assertJsonFragment(['brand_id' => $brand->id, 'label' => 'آیفون 11', 'secondary' => 'iPhone 11'])
            ->assertJsonMissing(['brand_id' => $otherBrand->id]);

        $this->getJson(route('listings.models', ['q' => 'iPhone 13']))
            ->assertOk()
            ->assertJsonFragment(['secondary' => 'iPhone 13']);
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
        $this->assertStringContainsString('modelsLoading', $script);
    }
}
