<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaInstallPromptConsoleTest extends TestCase
{
    public function test_public_install_flow_does_not_block_the_native_prompt(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $promptHandler = substr($script, strpos($script, "window.addEventListener('beforeinstallprompt'"));
        $promptHandler = substr($promptHandler, 0, strpos($promptHandler, "window.addEventListener('appinstalled'"));

        $this->assertStringContainsString('beforeinstallprompt', $script);
        $this->assertStringContainsString('this.canInstall = false', $script);
        $this->assertStringNotContainsString('event.preventDefault()', $promptHandler);
        $this->assertStringContainsString('x-show="isIos && !installed && !dismissed"', $layout);
    }
}
