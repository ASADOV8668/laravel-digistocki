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
        $seller = User::factory()->create();
        foreach (['iPhone 13 Pro Max', 'Redmi Note 12 Pro+'] as $modelName) {
            $model = PhoneModel::where('name_en', $modelName)->firstOrFail();
            Listing::create([
                'user_id' => $seller->id,
                'brand_id' => $model->brand_id,
                'phone_model_id' => $model->id,
                'title' => $model->name_fa,
                'slug' => Str::uuid(),
                'price' => 1000000,
                'status' => ListingStatus::Approved,
            ]);
        }

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
