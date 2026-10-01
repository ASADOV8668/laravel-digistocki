<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredNineDeploymentAssetsTest extends TestCase
{
    public function test_vite_and_apache_templates_keep_subdirectory_front_controller_configuration(): void
    {
        $vite = file_get_contents(base_path('vite.config.js'));
        $htaccess = file_get_contents(public_path('.htaccess'));
        $projectHtaccess = file_get_contents(base_path('.htaccess'));

        $this->assertIsString($vite);
        $this->assertIsString($htaccess);
        $this->assertIsString($projectHtaccess);
        $this->assertStringContainsString("loadEnv(mode, process.cwd(), '')", $vite);
        $this->assertStringContainsString('const buildBase = applicationBase ? `/${applicationBase}/build/` : \'/build/\'', $vite);
        $this->assertStringContainsString('RewriteEngine On', $htaccess);
        $this->assertStringContainsString('RewriteRule ^ /app/%1 [R=302,L,NE]', $htaccess);
        $this->assertStringContainsString('RewriteRule ^ index.php [L]', $htaccess);
        $this->assertStringContainsString('RewriteRule ^public(?:/(.*))?$ /app/$1 [R=302,L,NE]', $projectHtaccess);
    }
}
