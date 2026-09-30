<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFiftyTwoPwaInstallPromptTest extends TestCase
{
    public function test_public_layout_exposes_the_pwa_install_prompt(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('pwaInstallPrompt()', false)
            ->assertSee('افزودن به صفحه اصلی')
            ->assertSee('images/logo-192.png', false);
    }

    public function test_install_prompt_handles_install_lifecycle_and_remembers_dismissal(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString('window.pwaInstallPrompt', $script);
        $this->assertStringContainsString('beforeinstallprompt', $script);
        $this->assertStringContainsString('appinstalled', $script);
        $this->assertStringContainsString('digistocki-pwa-install-dismissed', $script);
    }

    public function test_admin_layout_does_not_render_public_install_prompt(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertNotFalse($layout);
        $this->assertStringNotContainsString('pwaInstallPrompt()', $layout);
    }
}
