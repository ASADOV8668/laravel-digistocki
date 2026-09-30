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

    public function test_admin_can_edit_user_information_and_disable_the_storefront(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $user = User::factory()->create(['mobile' => '09120004444']);
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'disable-shop',
            'name' => 'غرفه فعال',
            'is_enabled' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'نام ویرایش‌شده',
            'mobile' => '09120005555',
            'role' => 'user',
            'is_active' => 1,
            'can_post_listings' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $this->actingAs($admin)->put(route('admin.storefronts.update', $user), [
            'store_enabled' => 0,
            'name' => 'غرفه غیرفعال‌شده',
            'slug' => 'disable-shop',
        ])->assertRedirect(route('admin.storefronts.index'));

        $this->assertSame('نام ویرایش‌شده', $user->refresh()->name);
        $this->assertSame('09120005555', $user->mobile);
        $this->assertFalse($store->refresh()->is_enabled);
        $this->get(route('storefront.show', $store))->assertSee('غرفه این فروشنده غیرفعال است');
    }

    public function test_storefront_slug_must_be_unique_for_admin(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        SellerStore::create(['user_id' => $firstUser->id, 'slug' => 'shared-shop', 'name' => 'اول']);

        $this->actingAs($admin)->put(route('admin.storefronts.update', $secondUser), [
            'store_enabled' => 1,
            'name' => 'دوم',
            'slug' => 'shared-shop',
        ])->assertSessionHasErrors('slug');
    }
}
