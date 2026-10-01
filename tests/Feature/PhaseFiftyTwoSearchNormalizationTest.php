<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiftyTwoSearchNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
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
}
