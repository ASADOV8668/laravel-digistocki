<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFortyStoreApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_application_requires_the_new_identity_and_license_fields(): void
    {
        $user = User::factory()->create(['mobile' => '09120001111']);

        $this->actingAs($user)->put(route('storefront.update'), [
            'name' => 'فروشگاه تست',
            'slug' => 'test-store',
            'contact_phone' => '09120001111',
        ])->assertSessionHasErrors(['first_name', 'last_name', 'national_id', 'business_license_number', 'business_license_expires_at', 'province_id', 'city_id', 'address', 'business_license']);

        $this->assertDatabaseMissing('seller_stores', ['user_id' => $user->id]);
    }

    public function test_admin_approval_and_rejection_create_database_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['mobile' => '09120002222']);
        $store = SellerStore::create([
            'user_id' => $owner->id,
            'slug' => 'workflow-store',
            'name' => 'فروشگاه گردش کار',
            'approval_status' => 'pending',
            'is_enabled' => false,
        ]);

        $this->actingAs($admin)->patch(route('admin.storefronts.approve', $store))
            ->assertSessionHas('status', 'فروشگاه با موفقیت تأیید و فعال شد.');
        $this->assertTrue($store->fresh()->isPubliclyEnabled());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id, 'type' => 'App\\Notifications\\StorefrontStatusNotification']);

        $this->actingAs($admin)->patch(route('admin.storefronts.reject', $store), ['rejection_reason' => 'تصویر جواز خوانا نیست.'])
            ->assertSessionHas('status', 'درخواست فروشگاه رد شد و علت برای کاربر ثبت گردید.');
        $this->assertSame('rejected', $store->fresh()->approval_status);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id, 'type' => 'App\\Notifications\\StorefrontStatusNotification']);
    }
}
