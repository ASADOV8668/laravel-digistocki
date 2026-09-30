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

class PhaseOneHundredSeventySevenListingFilterStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtered_results_expose_removable_filter_chips_in_html_and_ajax(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $seller = User::factory()->create();
        $this->listing($seller, $brand->id, $model->id);

        $query = ['q' => 'آگهی', 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'min_price' => 100, 'sort' => 'price_asc'];
        $response = $this->get(route('listings.index', $query))->assertOk();
        $response->assertSee('فیلترهای فعال')->assertSee('برند: '.$brand->name)->assertSee('مرتب‌سازی: ارزان‌ترین');
        $this->assertStringContainsString(e(route('listings.index', ['q' => 'آگهی', 'phone_model_id' => $model->id, 'min_price' => 100, 'sort' => 'price_asc'])), $response->getContent());

        $this->getJson(route('listings.index', $query))
            ->assertOk()
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'فیلترهای فعال') && str_contains($html, 'پاک کردن همه'));
    }

    public function test_filter_state_uses_push_state_and_browser_back_reload(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));
        $this->assertNotFalse($script);
        $this->assertStringContainsString("historyMode = 'replace'", $script);
        $this->assertStringContainsString("fetchResults(url.toString(), 'push')", $script);
        $this->assertStringContainsString('window.history.pushState', $script);
        $this->assertStringContainsString("window.addEventListener('popstate'", $script);
    }

    private function listing(User $seller, int $brandId, int $modelId): Listing
    {
        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => 'آگهی فیلتر وضعیت',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
