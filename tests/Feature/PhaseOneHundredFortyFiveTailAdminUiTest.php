<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredFortyFiveTailAdminUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_use_the_tailadmin_rtl_shell_and_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('tailadmin-shell')
            ->assertSee('tailadmin-v2')
            ->assertSee('sidebarCollapsed')
            ->assertSee('admin-sidebar-collapsed')
            ->assertSee('menu-item-active')
            ->assertSee('admin-dark-mode')
            ->assertSee('جستجو در پنل مدیریت');

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('tailadmin-main')
            ->assertSee('menu-item');
    }

    public function test_regular_users_cannot_render_the_tailadmin_admin_shell(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
