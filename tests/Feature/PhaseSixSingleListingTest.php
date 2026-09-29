<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\ContactOtp;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_authenticated_user_must_verify_otp_before_phone_is_revealed(): void
    {
        $seller = User::factory()->create(['mobile' => '09129999999']);
        $viewer = User::factory()->create(['mobile' => '09121111112']);
        $listing = $this->listing($seller, 'آگهی نیازمند احراز');

        $this->actingAs($viewer)->get(route('listings.show', $listing))->assertDontSee($seller->mobile)->assertSee('دریافت کد تأیید');
        $this->actingAs($viewer)->post(route('listings.contact-otp', $listing))->assertRedirect()->assertSessionHas('contact_otp_listing_id', $listing->id);

        ContactOtp::latest()->firstOrFail()->update(['code_hash' => Hash::make('123456')]);
        $this->assertTrue(Hash::check('123456', ContactOtp::latest()->firstOrFail()->code_hash));
        $this->actingAs($viewer)->post(route('listings.contact-otp.verify', $listing), ['otp' => '000000'])->assertSessionHasErrors('otp');
        $this->actingAs($viewer)->post(route('listings.contact-otp.verify', $listing), ['otp' => '123456'])->assertRedirect()->assertSessionHas('revealed_contact_listings');
        $this->actingAs($viewer)->get(route('listings.show', $listing))->assertSee($seller->mobile)->assertSee('tel:'.$seller->mobile);
    }

    public function test_single_listing_shows_related_ads_from_same_model_or_brand(): void
    {
        $seller = User::factory()->create(['mobile' => '09129999999']);
        $main = $this->listing($seller, 'آگهی اصلی');
        $related = $this->listing(User::factory()->create(), 'آگهی مرتبط');

        $this->get(route('listings.show', $main))->assertOk()->assertSee('آگهی‌های مرتبط')->assertSee($related->title);
    }

    private function listing(User $seller, string $title): Listing
    {
        $brand = Brand::firstOrFail();
        return Listing::create(['user_id' => $seller->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->firstOrFail()->id, 'title' => $title, 'slug' => Str::uuid(), 'price' => 18000000, 'status' => ListingStatus::Approved, 'published_at' => now()]);
    }
}
