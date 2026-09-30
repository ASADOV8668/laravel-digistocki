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

class PhaseOneHundredSeventySixAjaxListingResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajax_listing_results_return_filtered_server_rendered_cards(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $seller = User::factory()->create();
        $matching = $this->listing($seller, $brand->id, $model->id, 'نتیجه Ajax');
        $this->listing($seller, $brand->id, $model->id, 'نتیجه دیگر');

        $this->getJson(route('listings.index', ['q' => 'Ajax', 'phone_model_id' => $model->id]))
            ->assertOk()
            ->assertJsonStructure(['html'])
            ->assertJsonPath('html', fn (string $html) => str_contains($html, $matching->title) && ! str_contains($html, 'نتیجه دیگر'));
    }

    public function test_listing_page_exposes_ajax_submit_pagination_and_loading_state(): void
    {
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('@submit.prevent="applyFilters($event)"', false)
            ->assertSee('id="listing-results"', false)
            ->assertSee('paginateResults', false)
            ->assertSee('در حال به‌روزرسانی نتایج', false);

        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertNotFalse($script);
        $this->assertStringContainsString('fetchResults(url, historyMode', $script);
        $this->assertStringContainsString('window.history.replaceState', $script);
    }

    private function listing(User $seller, int $brandId, int $modelId, string $title): Listing
    {
        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
