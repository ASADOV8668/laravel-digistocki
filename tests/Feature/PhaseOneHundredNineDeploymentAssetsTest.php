<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredNineDeploymentAssetsTest extends TestCase
{
    public function test_vite_and_apache_templates_keep_subdirectory_front_controller_configuration(): void
    {
        $vite = file_get_contents(base_path('vite.config.js'));
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertIsString($vite);
        $this->assertIsString($htaccess);
        $this->assertStringContainsString("base: '/app/build/'", $vite);
        $this->assertStringContainsString('RewriteEngine On', $htaccess);
        $this->assertStringContainsString('RewriteRule ^ index.php [L]', $htaccess);
    }
}
