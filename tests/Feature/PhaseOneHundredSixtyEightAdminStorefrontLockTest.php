<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixtyEightAdminStorefrontLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_cannot_reactivate_a_storefront_blocked_by_admin(): void
    {
        $user = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'locked-owner-shop',
            'name' => 'غرفه قفل‌شده',
            'is_enabled' => true,
            'is_admin_disabled' => true,
        ]);

        $this->actingAs($user)->get(route('storefront.edit'))
            ->assertOk()
            ->assertSee('غرفه شما توسط مدیریت غیرفعال شده است');

        $this->actingAs($user)->put(route('storefront.update'), [
            'store_enabled' => 1,
            'name' => 'غرفه قفل‌شده',
        ])->assertRedirect();

        $this->assertTrue($store->refresh()->is_enabled);
        $this->assertTrue($store->is_admin_disabled);
        $this->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee('غرفه این فروشنده غیرفعال است');
    }

    public function test_admin_can_remove_the_lock_without_changing_owner_preference(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $user = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'unlock-owner-shop',
            'name' => 'غرفه بازشدنی',
            'is_enabled' => true,
            'is_admin_disabled' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.storefronts.update', $user), [
            'store_enabled' => 1,
            'admin_disabled' => 0,
            'name' => $store->name,
            'slug' => $store->slug,
        ])->assertRedirect();

        $this->assertTrue($store->refresh()->is_enabled);
        $this->assertFalse($store->is_admin_disabled);
        $this->get(route('storefront.show', $store))->assertSee($store->name);
    }
}
