<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFortyFiveAdminUserAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_toggle_user_status_and_role_with_json(): void
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
            ->patchJson('/admin/users/'.$user->id.'/toggle-listing-permission', [])
            ->assertNotFound();

        $this->assertFalse($user->refresh()->is_active);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->can_post_listings);
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
