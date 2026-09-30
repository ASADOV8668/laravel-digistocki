<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseOneHundredThirtyFiveAdminUserEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_edit_user_details_and_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'mobile' => '09120000000']);
        $user = User::factory()->create(['name' => 'قدیمی', 'mobile' => '09121111111']);

        $this->actingAs($admin)->get(route('admin.users.edit', $user))->assertOk()->assertSee('قدیمی');
        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'جدید', 'mobile' => '+98 912 222 2222', 'password' => 'new-secret', 'password_confirmation' => 'new-secret', 'role' => 'user', 'is_active' => 1, 'can_post_listings' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('جدید', $user->name);
        $this->assertSame('09122222222', $user->mobile);
        $this->assertTrue(Hash::check('new-secret', $user->password));
    }

    public function test_admin_cannot_remove_the_last_admin_or_disable_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'mobile' => '09120000000']);
        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'mobile' => $admin->mobile, 'role' => 'user', 'is_active' => 0])->assertStatus(422);
    }
}
