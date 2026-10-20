<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\SellerStore;
use App\Models\User;
use App\Services\SystemOptions;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoHundredFiftyOnePublicListingPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_three_public_listing_pages_use_their_individual_page_size_settings(): void
    {
        $seller = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'pagination-shop',
            'name' => 'فروشگاه صفحه‌بندی',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);

        foreach (range(1, 3) as $number) {
            Listing::create([
                'user_id' => $seller->id,
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => "آگهی صفحه {$number}",
                'slug' => Str::uuid(),
                'price' => 10000000 + $number,
                'status' => ListingStatus::Approved,
                'published_at' => now()->subMinutes($number),
            ]);
        }

        app(SystemOptions::class)->setMany([
            'home_listings_per_page' => [2, 'integer'],
            'search_listings_per_page' => [2, 'integer'],
            'storefront_listings_per_page' => [1, 'integer'],
            'listings_pagination_type' => ['pagination', 'string'],
        ]);

        $this->get(route('home'))->assertViewHas('latestListings', fn ($listings) => $listings->perPage() === 2);
        $this->get(route('listings.index'))->assertViewHas('listings', fn ($listings) => $listings->perPage() === 2);
        $this->get(route('storefront.show', $store))->assertViewHas('listings', fn ($listings) => $listings->perPage() === 1);
    }

    public function test_infinite_mode_returns_next_page_metadata_and_card_markup_for_all_three_pages(): void
    {
        $seller = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'infinite-shop',
            'name' => 'فروشگاه اسکرولی',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);

        foreach (range(1, 2) as $number) {
            Listing::create([
                'user_id' => $seller->id,
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => "آگهی اسکرولی {$number}",
                'slug' => Str::uuid(),
                'price' => 12000000 + $number,
                'status' => ListingStatus::Approved,
                'published_at' => now()->subMinutes($number),
            ]);
        }

        app(SystemOptions::class)->setMany([
            'home_listings_per_page' => [1, 'integer'],
            'search_listings_per_page' => [1, 'integer'],
            'storefront_listings_per_page' => [1, 'integer'],
            'listings_pagination_type' => ['infinite', 'string'],
        ]);

        foreach ([route('home'), route('listings.index'), route('storefront.show', $store)] as $url) {
            $this->getJson($url)->assertOk()->assertJsonStructure(['html', 'next_page_url', 'has_more']);
        }

        $this->get(route('listings.index'))
            ->assertSee('data-pagination-mode="infinite"', false)
            ->assertSee('data-infinite-skeleton hidden class="hidden', false);
        $this->get(route('storefront.show', $store))
            ->assertSee('data-pagination-mode="infinite"', false)
            ->assertSee('data-infinite-skeleton hidden class="hidden', false);
        $this->get(route('home'))
            ->assertSee('data-pagination-mode="infinite"', false)
            ->assertSee('data-infinite-skeleton hidden class="hidden', false);
    }

    public function test_infinite_scroll_preserves_search_and_attribute_filters_for_search_and_storefront_pages(): void
    {
        $seller = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'filtered-infinite-shop',
            'name' => 'فروشگاه فیلتر اسکرولی',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);
        $storage = Attribute::query()->where('slug', 'storage')->firstOrFail();

        foreach (range(1, 2) as $number) {
            $listing = Listing::create([
                'user_id' => $seller->id,
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => "آیفون فیلتر اسکرول {$number}",
                'slug' => Str::uuid(),
                'price' => 20000000 + ($number * 1000000),
                'status' => ListingStatus::Approved,
                'published_at' => now()->subMinutes($number),
            ]);

            ListingAttributeValue::create([
                'listing_id' => $listing->id,
                'attribute_id' => $storage->id,
                'value_integer' => 256,
            ]);
        }

        app(SystemOptions::class)->setMany([
            'search_listings_per_page' => [1, 'integer'],
            'storefront_listings_per_page' => [1, 'integer'],
            'listings_pagination_type' => ['infinite', 'string'],
        ]);

        $filters = [
            'q' => 'آیفون',
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'filters' => [$storage->id => ['256']],
            'min_price' => 20000000,
            'max_price' => 23000000,
            'sort' => 'price_asc',
            'active_only' => '1',
        ];

        foreach ([
            route('listings.index', $filters),
            route('storefront.show', ['sellerStore' => $store->slug] + $filters),
        ] as $url) {
            $firstPage = $this->getJson($url)->assertOk()->assertJsonPath('has_more', true);
            $nextPageUrl = $firstPage->json('next_page_url');

            $this->assertNotEmpty($nextPageUrl);
            $nextQuery = $this->queryParameters($nextPageUrl);
            $this->assertSame('2', (string) ($nextQuery['page'] ?? null));
            $this->assertSame('آیفون', $nextQuery['q'] ?? null);
            $this->assertSame((string) $brand->id, (string) ($nextQuery['brand_id'] ?? null));
            $this->assertSame((string) $model->id, (string) ($nextQuery['phone_model_id'] ?? null));
            $this->assertSame(['256'], $nextQuery['filters'][$storage->id] ?? null);
            $this->assertSame('20000000', (string) ($nextQuery['min_price'] ?? null));
            $this->assertSame('23000000', (string) ($nextQuery['max_price'] ?? null));
            $this->assertSame('price_asc', $nextQuery['sort'] ?? null);
            $this->assertSame('1', (string) ($nextQuery['active_only'] ?? null));

            $secondPage = $this->getJson($nextPageUrl.'&infinite=1')->assertOk();
            $secondPageHtml = (string) $secondPage->json('html');
            $this->assertStringContainsString('آیفون فیلتر اسکرول 2', $secondPageHtml);
            $this->assertStringNotContainsString('آیفون فیلتر اسکرول 1', $secondPageHtml);
        }
    }

    public function test_public_listing_views_default_to_list_and_respect_the_one_year_view_cookie(): void
    {
        $seller = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'view-mode-shop',
            'name' => 'فروشگاه انتخاب نما',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);
        Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی انتخاب نما',
            'slug' => Str::uuid(),
            'price' => 15000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);

        $this->get(route('listings.index', ['q' => 'آیفون']))
            ->assertOk()
            ->assertSee('data-listing-view-link', false)
            ->assertSee('data-view="list"', false)
            ->assertSee('data-view="grid"', false)
            ->assertSee('q=%D8%A2%DB%8C%D9%81%D9%88%D9%86', false)
            ->assertSee('listing-card--list', false);

        $this->withCookie('listing_view', 'grid')
            ->get(route('listings.index'))
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('listing-card--grid', false)
            ->assertSee('grid-cols-2', false);

        $this->get(route('listings.index', ['view' => 'grid']))
            ->assertOk()
            ->assertCookie('listing_view', 'grid');

        $this->withCookie('listing_view', 'grid')
            ->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee('listing-card--grid', false)
            ->assertSee('data-view="grid"', false);

        $this->withCookie('listing_view', 'grid')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-listing-view-link', false)
            ->assertSee('listing-card--grid', false);
    }

    /** @return array<string, mixed> */
    private function queryParameters(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parameters);

        return $parameters;
    }
}
