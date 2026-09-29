<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseEightyThreePwaAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pwa_manifest_uses_raster_logo_assets_and_service_worker_caches_them(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        $icons = collect($manifest['icons'])->pluck('src')->all();

        $this->assertSame(['images/logo-192.png', 'images/logo-512.png'], $icons);
        $this->assertSame('image/png', $manifest['icons'][0]['type']);
        $this->assertFileExists(public_path('images/logo.png'));
        $this->assertFileExists(public_path('images/logo-192.png'));
        $this->assertFileExists(public_path('images/logo-512.png'));
        $this->assertFileExists(public_path('favicon.ico'));

        $serviceWorker = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("'./images/logo-192.png'", $serviceWorker);
        $this->assertStringContainsString("'./images/logo-512.png'", $serviceWorker);
        $this->assertStringContainsString("'./favicon.ico'", $serviceWorker);
    }
}
