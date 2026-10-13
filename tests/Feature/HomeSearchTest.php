<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoListingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CatalogSeeder::class, DemoListingSeeder::class]);
    }

    public function test_home_shows_latest_approved_listings_with_their_images(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('آخرین آگهی‌ها')
            ->assertSee('آیفون ۱۳ پرو تمیز و سالم')
            ->assertSee('storage/listings/demo-iphone.svg');
    }

    public function test_autocomplete_returns_matching_approved_listings(): void
    {
        $this->getJson(route('listings.autocomplete', ['q' => 'آیفون']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'آیفون ۱۳ پرو تمیز و سالم'])
            ->assertJsonFragment(['image' => asset('storage/listings/demo-iphone.svg')]);
    }

    public function test_autocomplete_ignores_terms_shorter_than_two_characters(): void
    {
        $this->getJson(route('listings.autocomplete', ['q' => 'ا']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_model_attribute_endpoint_returns_available_storage_and_color_values(): void
    {
        $model = \App\Models\PhoneModel::whereHas('brand', fn ($query) => $query->where('name_en', 'Apple'))->where('name_en', 'iPhone 13 Pro Max')->firstOrFail();
        $storage = \App\Models\Attribute::where('slug', 'storage')->firstOrFail();
        $color = \App\Models\Attribute::where('slug', 'color')->firstOrFail();

        $this->getJson(route('listings.models.attributes', $model))
            ->assertOk()
            ->assertJsonPath('0.slug', 'storage')
            ->assertJsonPath('0.values', [256])
            ->assertJsonPath('2.slug', 'color')
            ->assertJsonPath('2.values', ['طلایی']);

        $this->assertDatabaseHas('listing_attribute_values', ['attribute_id' => $storage->id, 'value_integer' => 256]);
        $this->assertDatabaseHas('listing_attribute_values', ['attribute_id' => $color->id, 'value_string' => 'طلایی']);
    }

    public function test_home_search_ui_exposes_model_storage_and_color_steps(): void
    {
        $view = file_get_contents(resource_path('views/welcome.blade.php'));

        $this->assertStringContainsString("url('/listings/models')", $view);
        $this->assertStringContainsString('quickAttributes()', $view);
        $this->assertStringContainsString('attributeChanged()', $view);
        $this->assertStringContainsString('filters[${attribute.id}]', $view);
    }
}
