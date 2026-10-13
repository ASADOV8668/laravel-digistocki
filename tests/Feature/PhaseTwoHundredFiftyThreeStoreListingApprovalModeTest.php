<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\SellerStore;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseTwoHundredFiftyThreeStoreListingApprovalModeTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_choose_free_listing_mode_for_a_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['mobile' => '09120001253']);
        $store = SellerStore::create([
            'user_id' => $owner->id,
            'slug' => 'approval-mode-store',
            'name' => 'فروشگاه مجوز ثبت',
            'approval_status' => 'approved',
            'is_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.storefronts.edit', $owner))
            ->assertOk()
            ->assertSee('name="listing_approval_mode"', false)
            ->assertSee('با تأیید مدیریت')
            ->assertSee('ثبت آزاد (بدون تأیید مدیریت)');

        $this->actingAs($admin)
            ->put(route('admin.storefronts.update', $owner), [
                'approval_status' => 'approved',
                'listing_approval_mode' => SellerStore::LISTING_APPROVAL_FREE,
                'name' => $store->name,
                'slug' => $store->slug,
            ])
            ->assertRedirect(route('admin.storefronts.index'));

        $this->assertSame(SellerStore::LISTING_APPROVAL_FREE, $store->refresh()->listing_approval_mode);
    }

    public function test_free_listing_store_publishes_new_listing_without_admin_approval(): void
    {
        $user = User::factory()->create();
        $store = $this->storeApplication($user);
        $store->update(['listing_approval_mode' => SellerStore::LISTING_APPROVAL_FREE]);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)
            ->post(route('listings.store'), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی ثبت آزاد',
                'price' => 32000000,
                'attributes' => $this->requiredAttributeValues($model),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'آگهی شما به‌صورت خودکار منتشر شد.');

        $listing = Listing::query()->where('title', 'آگهی ثبت آزاد')->firstOrFail();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNotNull($listing->published_at);
        $this->assertTrue($listing->expires_at->isFuture());
    }

    public function test_manual_listing_mode_remains_the_default_and_keeps_new_listings_pending(): void
    {
        $user = User::factory()->create();
        $store = $this->storeApplication($user);
        $this->assertSame(SellerStore::LISTING_APPROVAL_MANUAL, $store->refresh()->listing_approval_mode);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)
            ->post(route('listings.store'), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی نیازمند تأیید',
                'price' => 32000000,
                'attributes' => $this->requiredAttributeValues($model),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'آگهی شما برای بررسی ارسال شد.');

        $this->assertSame(
            ListingStatus::Pending,
            Listing::query()->where('title', 'آگهی نیازمند تأیید')->firstOrFail()->status,
        );
    }
}
