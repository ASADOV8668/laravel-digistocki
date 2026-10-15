<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFiftyNinePublicNavigationModesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_navigation_contains_ads_favorites_stores_chat_and_profile(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('آگهی‌ها')
            ->assertSee('موردعلاقه')
            ->assertSee('فروشگاه‌ها')
            ->assertSee('چت')
            ->assertSee('پروفایل')
            ->assertDontSee('href="' . route('listings.create') . '"', false);
    }

    public function test_listing_action_and_profile_top_action_are_available_for_a_user_who_can_post(): void
    {
        $user = User::factory()->create(['can_post_listings' => true]);
        SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'navigation-seller-store',
            'name' => 'فروشگاه ناوبری',
            'approval_status' => 'approved',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('ثبت آگهی')
            ->assertSee('href="' . route('listings.create') . '"', false)
            ->assertSee('aria-label="پروفایل"', false)
            ->assertSee('چت');
    }

    public function test_user_without_a_store_keeps_the_general_navigation_mode(): void
    {
        $user = User::factory()->create(['can_post_listings' => true]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="' . route('listings.create') . '"', false)
            ->assertSee('aria-label="اعلان‌ها"', false)
            ->assertSee('پروفایل');
    }
}
