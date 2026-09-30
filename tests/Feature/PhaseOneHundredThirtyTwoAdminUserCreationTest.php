<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseOneHundredThirtyTwoAdminUserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_with_role_and_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'کاربر مدیریت',
            'mobile' => '+98 912 345 6789',
            'national_id' => '۱۲۳۴۵۶۷۸۹۰',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
            'role' => 'admin',
            'is_active' => '1',
            'can_post_listings' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $user = User::query()->where('mobile', '09123456789')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->can_post_listings);
        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->assertSame('1234567890', $user->national_id);
    }

    public function test_regular_users_cannot_open_manual_user_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_mobile_must_be_unique_for_manual_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['mobile' => '09123456789']);

        $this->actingAs($admin)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'name' => 'تکراری', 'mobile' => '09123456789', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass', 'role' => 'user',
        ])->assertRedirect(route('admin.users.create'))->assertSessionHasErrors('mobile');
    }
}
