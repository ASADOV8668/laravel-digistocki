<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\SellerStore;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredSeventyStorefrontDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_storefront_supports_searching_the_sellers_published_listings(): void
    {
        $seller = User::factory()->create();
        $store = SellerStore::create(['user_id' => $seller->id, 'slug' => 'discovery-shop', 'name' => 'غرفه جستجو', 'is_enabled' => true]);
        $brand = Brand::firstOrFail();
        $matching = $this->listing($seller, 'آیفون برای فروش', $brand->id, $brand->phoneModels()->firstOrFail()->id);
        $otherBrand = Brand::query()->whereKeyNot($brand->id)->firstOrFail();
        $other = $this->listing($seller, 'گوشی سامسونگ', $otherBrand->id, $otherBrand->phoneModels()->firstOrFail()->id);

        $this->get(route('storefront.show', $store, false).'?q='.urlencode('آیفون'))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee($other->title)
            ->assertSee('storefront-search');
    }

    public function test_storefront_exposes_share_and_copy_link_controls(): void
    {
        $store = SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'share-shop', 'name' => 'غرفه اشتراک', 'is_enabled' => true]);

        $this->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee('اشتراک‌گذاری غرفه')
            ->assertSee('کپی لینک')
            ->assertSee('shareLink(', false);
    }

    private function listing(User $seller, string $title, int $brandId, int $modelId): Listing
    {
        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brandId,
            'phone_model_id' => $modelId,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 12000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
