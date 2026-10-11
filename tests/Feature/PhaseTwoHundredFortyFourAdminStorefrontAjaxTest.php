<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFortyFourAdminStorefrontAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_a_storefront_with_json_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $store = $this->store($owner, ['approval_status' => 'pending', 'is_enabled' => false]);

        $this->actingAs($admin)
            ->patchJson(route('admin.storefronts.approve', $store), [])
            ->assertOk()
            ->assertJson(['approval_status' => 'approved', 'status_label' => 'فعال', 'is_enabled' => true]);

        $this->assertTrue($store->refresh()->isPubliclyEnabled());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'type' => 'App\\Notifications\\StorefrontStatusNotification',
        ]);
    }

    public function test_admin_can_reject_a_storefront_with_json_response_and_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $store = $this->store($owner, ['approval_status' => 'pending', 'is_enabled' => false]);

        $this->actingAs($admin)
            ->patchJson(route('admin.storefronts.reject', $store), ['rejection_reason' => 'مدارک فروشگاه خوانا نیست.'])
            ->assertOk()
            ->assertJson(['approval_status' => 'rejected', 'status_label' => 'ردشده', 'is_enabled' => false]);

        $this->assertSame('مدارک فروشگاه خوانا نیست.', $store->refresh()->rejection_reason);
    }

    public function test_admin_can_toggle_storefront_block_with_json_response(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $store = $this->store($owner, ['approval_status' => 'approved', 'is_enabled' => true]);

        $this->actingAs($admin)
            ->patchJson(route('admin.storefronts.toggle', $store), [])
            ->assertOk()
            ->assertJson(['is_admin_disabled' => true, 'status_label' => 'مسدود توسط مدیریت']);

        $this->assertTrue($store->refresh()->is_admin_disabled);
    }

    private function store(User $owner, array $overrides = []): SellerStore
    {
        return SellerStore::create(array_merge([
            'user_id' => $owner->id,
            'slug' => 'ajax-store-'.$owner->id,
            'name' => 'فروشگاه آجاکس',
            'approval_status' => 'pending',
            'is_enabled' => false,
            'is_admin_disabled' => false,
        ], $overrides));
    }
}
