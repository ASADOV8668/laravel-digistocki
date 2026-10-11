<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFortyFiveAdminUserAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_user_status_role_and_listing_permission_with_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
            'can_post_listings' => false,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.toggle-active', $user), [])
            ->assertOk()
            ->assertJson(['is_active' => false]);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.toggle-role', $user), [])
            ->assertOk()
            ->assertJson(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.toggle-listing-permission', $user), [])
            ->assertOk()
            ->assertJson(['can_post_listings' => true]);

        $this->assertFalse($user->refresh()->is_active);
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->can_post_listings);
    }

    public function test_admin_cannot_toggle_own_account_with_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.toggle-active', $admin), [])
            ->assertStatus(422);

        $this->assertTrue($admin->refresh()->is_active);
    }
}
