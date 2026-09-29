<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseFourteenNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_owner_receives_database_notification_when_listing_is_approved(): void
    {
        [$owner, $brand, $model] = $this->context();
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $listing = $this->listing($owner, $brand, $model, ListingStatus::Pending);

        $this->actingAs($admin)->patch(route('admin.listings.approve', $listing))->assertRedirect();

        $notification = $owner->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('approved', $notification->data['status']);
        $this->assertNull($notification->read_at);

        $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('آگهی تأیید شد');
        $this->actingAs($owner)->patch(route('notifications.read', $notification->id))->assertRedirect()->assertSessionHas('status', 'اعلان خوانده‌شده علامت خورد.');
        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('اعلان خوانده‌شده علامت خورد.');
    }

    public function test_rejected_listing_notification_contains_reason_and_all_can_be_marked_read(): void
    {
        [$owner, $brand, $model] = $this->context();
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $listing = $this->listing($owner, $brand, $model, ListingStatus::Pending);

        $this->actingAs($admin)->patch(route('admin.listings.reject', $listing), ['rejection_reason' => 'تصویر واضح نیست'])->assertRedirect();

        $notification = $owner->notifications()->first();
        $this->assertSame('rejected', $notification->data['status']);
        $this->assertSame('تصویر واضح نیست', $notification->data['reason']);

        $this->actingAs($owner)->patch(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $owner->fresh()->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        [$owner, $brand, $model] = $this->context();
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $listing = $this->listing($owner, $brand, $model, ListingStatus::Pending);
        $this->actingAs($admin)->patch(route('admin.listings.approve', $listing));
        $notification = $owner->notifications()->firstOrFail();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->patch(route('notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    private function context(): array
    {
        $brand = Brand::firstOrFail();

        return [User::factory()->create(), $brand, $brand->phoneModels()->firstOrFail()];
    }

    private function listing(User $user, Brand $brand, PhoneModel $model, ListingStatus $status): Listing
    {
        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی اعلان '.Str::random(5),
            'slug' => Str::uuid()->toString(),
            'price' => 1000000,
            'status' => $status,
        ]);
    }
}
