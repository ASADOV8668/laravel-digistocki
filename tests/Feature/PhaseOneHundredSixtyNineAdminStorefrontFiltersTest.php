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
        $active = SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'active-filter-shop', 'name' => 'غرفه فعال', 'is_enabled' => true]);
        SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'disabled-filter-shop', 'name' => 'غرفه خاموش', 'is_enabled' => false]);
        SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'blocked-filter-shop', 'name' => 'غرفه مسدود', 'is_enabled' => true, 'is_admin_disabled' => true]);

        $this->actingAs($admin)->get(route('admin.storefronts.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee('غرفه خاموش')
            ->assertDontSee('غرفه مسدود')
            ->assertSee('فعال و قابل مشاهده');
    }

    public function test_unknown_storefront_status_is_ignored(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $store = SellerStore::create(['user_id' => User::factory()->create()->id, 'slug' => 'all-filter-shop', 'name' => 'غرفه همه', 'is_enabled' => true]);

        $this->actingAs($admin)->get(route('admin.storefronts.index', ['status' => 'unknown']))
            ->assertOk()
            ->assertSee($store->name)
            ->assertSee('کل غرفه‌ها');
    }
}
