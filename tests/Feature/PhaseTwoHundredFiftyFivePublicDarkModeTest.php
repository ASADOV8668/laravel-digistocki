<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredFiftyFivePublicDarkModeTest extends TestCase
{
    public function test_public_dark_mode_uses_a_one_year_cookie_and_has_guest_and_authenticated_controls(): void
    {
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $guestLayout = file_get_contents(resource_path('views/layouts/guest.blade.php'));
        $topBar = file_get_contents(resource_path('views/components/top-bar.blade.php'));
        $sidebar = file_get_contents(resource_path('views/components/sidebar.blade.php'));
        $javascript = file_get_contents(resource_path('js/app.js'));
        $stylesheet = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('public_theme', $appLayout);
        $this->assertStringContainsString('public_theme', $guestLayout);
        $this->assertStringContainsString('data-theme-toggle', $topBar);
        $this->assertStringContainsString('data-theme-toggle', $sidebar);
        $this->assertStringContainsString('publicThemeMaxAge = 60 * 60 * 24 * 365', $javascript);
        $this->assertStringContainsString('SameSite=Lax', $javascript);
        $this->assertStringContainsString("document.documentElement.classList.toggle('dark'", $javascript);
        $this->assertStringContainsString('darkMode: \'class\'', file_get_contents(base_path('tailwind.config.js')));
        $this->assertStringContainsString('dark:bg-slate-950', $stylesheet);
        $this->assertStringContainsString('dark:bg-slate-900', $stylesheet);
    }
}
