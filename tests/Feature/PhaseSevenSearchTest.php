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

class PhaseSevenSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_search_suggestions_find_models_with_persian_and_english_terms(): void
    {
        $persian = $this->getJson(route('listings.search.suggestions', ['q' => 'آیفون']))->assertOk();
        $persian->assertJsonFragment(['label' => 'آیفون 13 پرو']);

        $english = $this->getJson(route('listings.search.suggestions', ['q' => 'iPhone 13']))->assertOk();
        $english->assertJsonFragment(['secondary' => 'iPhone 13 Pro']);
    }

    public function test_model_attributes_endpoint_returns_only_filterable_attributes_for_selected_model(): void
    {
        $model = PhoneModel::where('name', 'iPhone 13 Pro')->firstOrFail();

        $this->getJson(route('listings.models.attributes', $model))
            ->assertOk()
            ->assertJsonFragment(['name' => 'حافظه داخلی', 'type' => 'integer'])
            ->assertJsonFragment(['name' => 'رنگ', 'type' => 'select'])
            ->assertJsonFragment(['name' => 'جعبه و لوازم', 'type' => 'boolean']);
    }

    public function test_index_search_matches_listing_model_in_both_languages(): void
    {
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->where('name', 'iPhone 13 Pro')->firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'گوشی تمیز با عنوان متفاوت',
            'slug' => Str::uuid(),
            'price' => 42000000,
            'status' => ListingStatus::Approved,
        ]);

        $this->get(route('listings.index', ['q' => 'آیفون']))->assertOk()->assertSee($listing->title);
        $this->get(route('listings.index', ['q' => 'iPhone 13']))->assertOk()->assertSee($listing->title);
    }
}
