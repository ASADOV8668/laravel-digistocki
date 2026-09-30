<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredFortySixAdminSurfaceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function adminSurfaceRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'listings' => ['admin.listings.index'],
            'users' => ['admin.users.index'],
            'reports' => ['admin.reports.index'],
            'brands' => ['admin.brands.index'],
            'phone models' => ['admin.phone-models.index'],
            'attributes' => ['admin.attributes.index'],
            'settings' => ['admin.settings.edit'],
        ];
    }

    /** @dataProvider adminSurfaceRoutes */
    public function test_every_admin_surface_renders_inside_the_tailadmin_shell(string $routeName): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('tailadmin-shell')
            ->assertSee('tailadmin-main')
            ->assertSee('menu-item')
            ->assertSee('admin-card');
    }
}
