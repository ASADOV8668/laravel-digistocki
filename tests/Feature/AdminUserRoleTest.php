<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_senior_admin_can_assign_department_and_system_roles(): void
    {
        $senior = User::factory()->create(['role' => 'admin', 'is_senior_admin' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $route = route('admin.users.role.update', $user);

        $this->actingAs($admin)->patch($route, ['role' => 'technical'])->assertForbidden();
        $this->actingAs($senior)->get(route('admin.users.index'))->assertOk()
            ->assertSee('مدیر فنی')->assertSee('مدیر حسابداری')->assertSee('مدیر سیستم');

        foreach (['technical', 'accounting', 'admin', 'user'] as $role) {
            $this->patch($route, ['role' => $role])->assertRedirect();
            $this->assertSame($role, $user->fresh()->administrationRole());
            $this->assertSame($role === 'admin', $user->fresh()->isAdmin());
        }

        $this->patch($route, ['role' => 'senior'])->assertSessionHasErrors('role');
        $this->assertFalse($user->fresh()->isSeniorAdmin());
    }

    public function test_summary_cards_link_to_filtered_user_lists(): void
    {
        $senior = User::factory()->create(['role' => 'admin', 'is_senior_admin' => true, 'is_active' => true]);
        $systemAdmin = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        User::factory()->create(['name' => 'حساب نمونه', 'is_active' => true]);
        User::factory()->create(['name' => 'حساب نمونه غیرفعال', 'is_active' => false]);

        $this->actingAs($senior)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.index', ['active' => '1']))
            ->assertSee(route('admin.users.index', ['role' => 'admin']))
            ->assertViewHas('users', fn ($users) => $users->total() === 4);

        $this->get(route('admin.users.index', ['active' => '1', 'q' => 'حساب نمونه', 'per_page' => 25]))
            ->assertOk()
            ->assertSee('aria-current="true"', false)
            ->assertSee(route('admin.users.index', ['q' => 'حساب نمونه', 'per_page' => 25]))
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->items()[0]->is_active);

        $this->get(route('admin.users.index', ['role' => 'admin']))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->items()[0]->is($systemAdmin))
            ->assertViewHas('userStats', fn ($stats) => $stats['admins'] === 1);
    }

    public function test_regular_admin_cannot_change_role_using_general_edit_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['mobile' => '09121234567', 'department_role' => 'technical']);
        $data = ['name' => 'نام جدید', 'mobile' => $user->mobile, 'role' => 'accounting', 'is_active' => true];

        $this->actingAs($admin)->put(route('admin.users.update', $user), $data)->assertForbidden();
        $this->put(route('admin.users.update', $user), [...$data, 'role' => 'technical'])->assertRedirect();
        $this->assertSame('technical', $user->fresh()->administrationRole());
        $this->assertSame('نام جدید', $user->fresh()->name);
        $this->actingAs($user->fresh())->get(route('admin.dashboard'))->assertForbidden();
    }
}
