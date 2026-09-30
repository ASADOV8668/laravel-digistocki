<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredNinetyThreeAdminAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_shell_exposes_navigation_relationships_for_mobile_and_screen_readers(): void
    {
        $view = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertNotFalse($view);
        $this->assertStringContainsString('id="admin-sidebar"', $view);
        $this->assertStringContainsString('role="navigation"', $view);
        $this->assertStringContainsString('aria-label="ناوبری مدیریت"', $view);
        $this->assertStringContainsString('aria-controls="admin-sidebar"', $view);
        $this->assertStringContainsString(':aria-expanded="sidebarOpen.toString()"', $view);
        $this->assertStringContainsString('aria-hidden="true"', $view);
    }
}
