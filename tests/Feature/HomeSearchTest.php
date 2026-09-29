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
}
