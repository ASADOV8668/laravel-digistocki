<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFiftySevenIosPwaInstallTest extends TestCase
{
    public function test_public_layout_contains_the_ios_install_guidance(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('در Safari روی دکمه اشتراک‌گذاری بزنید')
            ->assertSee('افزودن به صفحه اصلی')
            ->assertSee('isIos', false);
    }

    public function test_pwa_prompt_detects_ios_and_ipad_desktop_mode(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertNotFalse($script);
        $this->assertStringContainsString('isIos: false', $script);
        $this->assertStringContainsString('/iphone|ipad|ipod/i', $script);
        $this->assertStringContainsString("window.navigator.platform === 'MacIntel'", $script);
        $this->assertStringContainsString('window.navigator.maxTouchPoints > 1', $script);
    }

    public function test_ios_prompt_is_not_rendered_in_admin_layout(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertNotFalse($layout);
        $this->assertStringNotContainsString('isIos', $layout);
    }
}
