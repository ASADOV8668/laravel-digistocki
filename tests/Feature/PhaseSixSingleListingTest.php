<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\SellerStore;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSixSingleListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_guest_sees_single_listing_without_seller_phone_and_is_sent_to_login(): void
    {
        $seller = User::factory()->create(['mobile' => '09129999999']);
        $listing = $this->listing($seller, 'آگهی امن تماس');

        $this->get(route('listings.show', $listing))->assertOk()->assertSee($listing->title)->assertDontSee($seller->mobile)->assertSee('application/ld+json', false)->assertSee('"@type":"Product"', false);
        $this->post(route('listings.contact-otp', $listing))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_the_seller_phone_without_contact_otp(): void
    {
        $seller = User::factory()->create(['mobile' => '09129999999']);
        $viewer = User::factory()->create(['mobile' => '09121111112']);
        $listing = $this->listing($seller, 'آگهی نیازمند احراز');

        $this->actingAs($viewer)->get(route('listings.show', $listing))
            ->assertSee($seller->mobile)
            ->assertSee('tel:'.$seller->mobile)
            ->assertDontSee('دریافت کد تأیید');
    }

    public function test_single_listing_shows_related_ads_from_same_model_or_brand(): void
    {
        $seller = User::factory()->create(['mobile' => '09129999999']);
        $main = $this->listing($seller, 'آگهی اصلی');
        $related = $this->listing(User::factory()->create(), 'آگهی مرتبط');

        $this->get(route('listings.show', $main))->assertOk()->assertSee('آگهی‌های مرتبط')->assertSee($related->title);
    }

    public function test_single_listing_shows_the_public_store_logo_and_name(): void
    {
        $seller = User::factory()->create(['name' => 'نام شخصی فروشنده']);
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'single-listing-store',
            'name' => 'فروشگاه آیفون مرکزی',
            'logo_path' => 'storefronts/logo.jpg',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);
        $listing = $this->listing($seller, 'آگهی فروشگاه‌دار');

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee($store->name)
            ->assertSee(asset('storage/'.$store->logo_path), false)
            ->assertDontSee('نام شخصی فروشنده');
    }

    public function test_single_listing_exposes_listing_metadata_for_search_and_social_sharing(): void
    {
        $seller = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $seller->id,
            'slug' => 'metadata-store',
            'name' => 'فروشگاه متا',
            'address' => 'خیابان اصلی تهران',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);
        $listing = $this->listing($seller, 'آگهی متادیتا');
        $attribute = Attribute::query()->where('slug', 'storage')->firstOrFail();
        ListingAttributeValue::create([
            'listing_id' => $listing->id,
            'attribute_id' => $attribute->id,
            'value_integer' => 128,
        ]);

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee('name="description" content="آگهی متادیتا | حافظه داخلی: ۱۲۸ GB | فروشگاه متا | خیابان اصلی تهران"', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:image"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="twitter:image"', false);
    }

    private function listing(User $seller, string $title): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create(['user_id' => $seller->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->firstOrFail()->id, 'title' => $title, 'slug' => Str::uuid(), 'price' => 18000000, 'status' => ListingStatus::Approved, 'published_at' => now()]);
    }
}
