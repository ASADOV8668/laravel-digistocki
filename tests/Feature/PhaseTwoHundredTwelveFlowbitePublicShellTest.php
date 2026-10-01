<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwelveFlowbitePublicShellTest extends TestCase
{
    public function test_public_shell_exposes_flowbite_drawer_bottom_navigation_and_toast_hooks(): void
    {
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('data-drawer-target="public-sidebar"', false)
            ->assertSee('data-drawer-show="public-sidebar"', false)
            ->assertSee('id="public-sidebar"', false)
            ->assertSee('data-drawer-hide="public-sidebar"', false)
            ->assertSee('id="public-bottom-navigation"', false)
            ->assertSee('id="public-toast"', false);
    }

    public function test_admin_shell_does_not_render_public_flowbite_navigation(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertDontSee('public-sidebar', false)
            ->assertDontSee('public-bottom-navigation', false);
    }
}
