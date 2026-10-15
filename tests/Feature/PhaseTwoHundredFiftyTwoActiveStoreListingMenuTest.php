<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFiftyTwoActiveStoreListingMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_listings_menu_is_only_visible_for_a_publicly_enabled_storefront(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertDontSee('آگهی‌های من');

        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'pending-listing-menu-store',
            'name' => 'فروشگاه در انتظار بررسی',
            'approval_status' => 'pending',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertDontSee('آگهی‌های من');

        $store->forceFill([
            'approval_status' => 'approved',
            'is_enabled' => true,
            'is_admin_disabled' => false,
        ])->save();
        $user->refresh();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('آگهی‌های من')
            ->assertSee('href="'.route('dashboard').'"', false);
    }

    public function test_my_listings_menu_is_hidden_when_management_disabled_the_storefront(): void
    {
        $user = User::factory()->create();
        SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'disabled-listing-menu-store',
            'name' => 'فروشگاه غیرفعال',
            'approval_status' => 'approved',
            'is_enabled' => true,
            'is_admin_disabled' => true,
        ]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertDontSee('آگهی‌های من');
    }
}
