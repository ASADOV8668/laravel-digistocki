<?php

namespace Tests\Feature;

use App\Models\Brand;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEighteenCatalogLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_seeder_keeps_common_persian_brand_and_model_names_distinct(): void
    {
        $this->seed(CatalogSeeder::class);

        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->where('name_en', 'iPhone 13 Pro')->firstOrFail();

        $this->assertSame('اپل', $brand->name);
        $this->assertSame('آیفون 13 پرو', $model->name_fa);
        $this->assertSame('iPhone 13 Pro', $model->name_en);
    }

    public function test_model_suggestions_match_both_persian_and_english_model_names(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->getJson(route('listings.search.suggestions', ['q' => 'آیفون ۱۳ پرو مکس']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'آیفون 13 پرو مکس', 'secondary' => 'iPhone 13 Pro Max']);

        $this->getJson(route('listings.search.suggestions', ['q' => 'iPhone 13 Pro Max']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'آیفون 13 پرو مکس', 'secondary' => 'iPhone 13 Pro Max']);

        $this->getJson(route('listings.search.suggestions', ['q' => 'ردمی نوت ۱۲ پرو پلاس']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'ردمی نوت 12 پرو پلاس', 'secondary' => 'Redmi Note 12 Pro+']);
    }
}
