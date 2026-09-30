<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredNinetyEightLayoutLandmarksTest extends TestCase
{
    public function test_public_and_admin_layouts_expose_named_main_landmarks(): void
    {
        $publicLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $adminLayout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $sidebar = file_get_contents(resource_path('views/components/sidebar.blade.php'));

        $this->assertStringContainsString('<main id="main-content" tabindex="-1" aria-label="محتوای اصلی">', $publicLayout);
        $this->assertStringContainsString('aria-label="محتوای اصلی پنل مدیریت"', $adminLayout);
        $this->assertStringContainsString('aria-label="ناوبری اصلی"', $sidebar);
    }
}
