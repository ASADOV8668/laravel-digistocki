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

class PhaseFortyThreeSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_sitemap_contains_public_pages_and_only_published_listings(): void
    {
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $published = $this->listing($brand->id, $model->id, ListingStatus::Approved, 'آگهی قابل ایندکس');
        $pending = $this->listing($brand->id, $model->id, ListingStatus::Pending, 'آگهی غیرقابل ایندکس');

        $response = $this->get(route('seo.sitemap'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('listings.show', $published), false)
            ->assertDontSee(route('listings.show', $pending), false);
    }

    public function test_robots_excludes_private_areas_and_points_to_sitemap(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /dashboard')
            ->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    private function listing(int $brandId, int $modelId, ListingStatus $status, string $title): Listing
    {
        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => $status,
            'published_at' => $status === ListingStatus::Approved ? now() : null,
        ]);
    }
}
