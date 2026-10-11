<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixtyNineAdminStorefrontFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_storefronts_by_public_status(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $active = SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'active-filter-shop', 'name' => 'فروشگاه فعال', 'is_enabled' => true, 'approval_status' => 'approved']);
        SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'disabled-filter-shop', 'name' => 'فروشگاه خاموش', 'is_enabled' => false, 'approval_status' => 'approved']);
        SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'blocked-filter-shop', 'name' => 'فروشگاه مسدود', 'is_enabled' => true, 'is_admin_disabled' => true, 'approval_status' => 'approved']);

        $this->actingAs($admin)->get(route('admin.storefronts.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee('فروشگاه خاموش')
            ->assertDontSee('فروشگاه مسدود')
            ->assertSee('فعال و قابل مشاهده');
    }

    public function test_unknown_storefront_status_is_ignored(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $store = SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'all-filter-shop', 'name' => 'فروشگاه همه', 'is_enabled' => true, 'approval_status' => 'approved']);

        $this->actingAs($admin)->get(route('admin.storefronts.index', ['status' => 'unknown']))
            ->assertOk()
            ->assertSee($store->name)
            ->assertSee('کل فروشگاه‌ها');
    }
}
