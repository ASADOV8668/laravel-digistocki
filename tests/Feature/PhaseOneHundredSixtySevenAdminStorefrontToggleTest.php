<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixtySevenAdminStorefrontToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_storefront_from_the_management_list(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $store = SellerStore::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'quick-toggle-shop',
            'name' => 'غرفه تغییر سریع',
            'is_enabled' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.storefronts.index'))
            ->assertOk()
            ->assertSee('غیرفعال‌سازی');

        $this->actingAs($admin)->patch(route('admin.storefronts.toggle', $store))
            ->assertRedirect()
            ->assertSessionHas('status', 'غرفه توسط مدیریت غیرفعال شد.');

        $this->assertTrue($store->refresh()->is_enabled);
        $this->assertTrue($store->is_admin_disabled);
    }

    public function test_regular_user_cannot_toggle_a_storefront(): void
    {
        $user = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'protected-toggle-shop',
            'name' => 'غرفه محافظت‌شده',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)->patch(route('admin.storefronts.toggle', $store))->assertForbidden();
        $this->assertTrue($store->refresh()->is_enabled);
    }
}
