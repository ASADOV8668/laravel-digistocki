<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredEightyTwoServerRenderedRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_pages_are_server_rendered_html_routes(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی مسیر Laravel',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);

        foreach ([
            route('home'),
            route('listings.index'),
            route('listings.show', $listing),
        ] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
        }
    }

    public function test_listing_ajax_enhancement_returns_html_fragment_only_for_explicit_json_requests(): void
    {
        $this->getJson(route('listings.index'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['html']);
    }
}
