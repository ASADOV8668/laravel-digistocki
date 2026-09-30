<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseEightyFourLegacyNavigationIconsTest extends TestCase
{
    public function test_legacy_navigation_uses_digistocki_logo_and_heroicons(): void
    {
        $logo = file_get_contents(resource_path('views/components/application-logo.blade.php'));
        $navigation = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        $this->assertStringContainsString('images/logo-header.svg', $logo);
        $this->assertStringNotContainsString('<svg', $logo);
        $this->assertStringContainsString('x-heroicon-o-chevron-down', $navigation);
        $this->assertStringContainsString('x-heroicon-o-bars-3', $navigation);
        $this->assertStringContainsString('x-heroicon-o-x-mark', $navigation);
        $this->assertStringNotContainsString('<svg', $navigation);
    }
}
