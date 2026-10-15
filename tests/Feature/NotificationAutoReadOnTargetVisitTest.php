<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\SellerStore;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use App\Notifications\PendingListingNotification;
use App\Notifications\PendingStorefrontNotification;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationAutoReadOnTargetVisitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_listing_review_marks_matching_notification_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $listing = $this->listing($owner, 'آگهی اعلان مدیر');
        $admin->notify(new PendingListingNotification($listing));
        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)->get(route('admin.listings.edit', $listing))->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_storefront_review_marks_matching_notification_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $owner->id,
            'slug' => 'notification-store',
            'name' => 'فروشگاه اعلان',
            'approval_status' => 'pending',
            'is_enabled' => false,
        ]);
        $admin->notify(new PendingStorefrontNotification($store));
        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)->get(route('admin.storefronts.edit', $owner))->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_viewing_their_listing_marks_matching_status_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $listing = $this->listing($owner, 'آگهی تأییدشده', ListingStatus::Approved);
        $owner->notify(new ListingStatusNotification($listing, 'approved'));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($owner)->get(route('listings.show', $listing))->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function listing(User $owner, string $title, ListingStatus $status = ListingStatus::Pending): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => Str::uuid()->toString(),
            'price' => 1000000,
            'status' => $status,
        ]);
    }
}
