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

class PhaseTwoHundredFortyThreeAdminModerationAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_approve_a_listing_with_json_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $listing = $this->listing($owner, ListingStatus::Pending, 'آگهی تأیید آجاکس');

        $this->actingAs($admin)
            ->patchJson(route('admin.listings.approve', $listing))
            ->assertOk()
            ->assertJson(['status' => 'approved', 'status_label' => 'تأیید شده']);

        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'type' => 'App\\Notifications\\ListingStatusNotification',
        ]);
    }

    public function test_admin_can_reject_a_listing_with_json_response_and_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $listing = $this->listing($owner, ListingStatus::Pending, 'آگهی رد آجاکس');

        $this->actingAs($admin)
            ->patchJson(route('admin.listings.reject', $listing), ['rejection_reason' => 'تصویر آگهی خوانا نیست.'])
            ->assertOk()
            ->assertJson(['status' => 'rejected', 'status_label' => 'رد شده']);

        $this->assertSame(ListingStatus::Rejected, $listing->refresh()->status);
        $this->assertSame('تصویر آگهی خوانا نیست.', $listing->rejection_reason);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'type' => 'App\\Notifications\\ListingStatusNotification',
        ]);
    }

    private function listing(User $owner, ListingStatus $status, string $title): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => $status,
        ]);
    }
}
