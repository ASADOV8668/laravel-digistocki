<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixtySixUserStorefrontDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_enabled_storefront_status_and_public_link(): void
    {
        $user = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'dashboard-shop',
            'name' => 'غرفه داشبورد',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($store->name)
            ->assertSee('فعال و قابل مشاهده')
            ->assertSee('مشاهده فروشگاه')
            ->assertSee(route('storefront.show', $store), false);
    }

    public function test_dashboard_shows_store_logo_and_share_button_without_printing_store_path(): void
    {
        $user = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'dashboard-logo-shop',
            'name' => 'فروشگاه لوگودار',
            'logo_path' => 'stores/dashboard-logo.webp',
            'is_enabled' => true,
            'approval_status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(asset('storage/'.$store->logo_path), false)
            ->assertSee('اشتراک‌گذاری فروشگاه')
            ->assertDontSee('/store/'.$store->slug.'</p>', false);
    }

    public function test_dashboard_shows_disabled_storefront_without_public_view_link(): void
    {
        $user = User::factory()->create();
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'disabled-dashboard-shop',
            'name' => 'غرفه غیرفعال داشبورد',
            'is_enabled' => false,
            'approval_status' => 'approved',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($store->name)
            ->assertSee('غیرفعال')
            ->assertSee('ویرایش اطلاعات');
    }

    public function test_dashboard_promotes_storefront_creation_when_user_has_no_store(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('فروشگاه خودت را ثبت کن')
            ->assertSee(route('storefront.edit'), false);
    }
}
