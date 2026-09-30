<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixtyOneStorefrontManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_storefront_with_an_english_slug(): void
    {
        $user = User::factory()->create(['mobile' => '09120001111']);

        $this->actingAs($user)->get(route('storefront.edit'))
            ->assertOk()
            ->assertSee('غرفه شما');

        $this->actingAs($user)->put(route('storefront.update'), [
            'store_enabled' => 1,
            'name' => 'فروشگاه رضایی',
            'slug' => 'reza-mobile',
            'contact_phone' => '09120001111',
        ])->assertRedirect()->assertSessionHas('status', 'اطلاعات غرفه شما ذخیره شد.');

        $store = SellerStore::where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($store->is_enabled);
        $this->assertSame('reza-mobile', $store->slug);
        $this->assertSame('09120001111', $store->contact_phone);
    }

    public function test_existing_storefront_slug_cannot_be_changed_by_the_owner(): void
    {
        $user = User::factory()->create();
        SellerStore::create(['user_id' => $user->id, 'slug' => 'locked-store', 'name' => 'غرفه قفل‌شده']);

        $this->actingAs($user)->put(route('storefront.update'), [
            'store_enabled' => 1,
            'name' => 'غرفه جدید',
            'slug' => 'new-store',
        ])->assertSessionHasErrors('slug');

        $this->assertSame('locked-store', $user->storefront->fresh()->slug);
    }

    public function test_invalid_store_slug_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('storefront.update'), [
            'store_enabled' => 1,
            'name' => 'غرفه نامعتبر',
            'slug' => 'غرفه فارسی',
        ])->assertSessionHasErrors('slug');
    }
}
