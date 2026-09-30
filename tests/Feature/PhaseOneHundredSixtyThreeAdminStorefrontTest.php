<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixtyThreeAdminStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_a_storefront_slug(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $user = User::factory()->create(['mobile' => '09120003333']);

        $this->actingAs($admin)->get(route('admin.storefronts.index'))->assertOk()->assertSee('مدیریت غرفه‌ها');
        $this->actingAs($admin)->put(route('admin.storefronts.update', $user), [
            'store_enabled' => 1,
            'name' => 'غرفه مدیریتی',
            'slug' => 'admin-shop',
            'contact_phone' => '09120003333',
        ])->assertRedirect(route('admin.storefronts.index'));

        $store = SellerStore::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('admin-shop', $store->slug);

        $this->actingAs($admin)->put(route('admin.storefronts.update', $user), [
            'store_enabled' => 1,
            'name' => 'غرفه تغییر یافته',
            'slug' => 'renamed-shop',
        ])->assertRedirect();

        $this->assertSame('renamed-shop', $store->refresh()->slug);
    }

    public function test_regular_user_cannot_manage_storefronts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.storefronts.index'))->assertForbidden();
    }
}
