<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFiftyOneBrandLogoAndManifestTest extends TestCase
{
    public function test_brand_logo_assets_are_standardized_to_the_new_source(): void
    {
        foreach (['logo.png' => 512, 'logo-512.png' => 512, 'logo-192.png' => 192] as $filename => $size) {
            $path = public_path('images/'.$filename);
            $this->assertFileExists($path);
            $dimensions = getimagesize($path);
            $this->assertIsArray($dimensions);
            $this->assertSame($size, $dimensions[0]);
            $this->assertSame($size, $dimensions[1]);
        }

        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertGreaterThan(100, filesize(public_path('favicon.ico')));
    }

    public function test_manifest_is_configured_for_installable_rtl_home_screen(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('دیجی استوکی', $manifest['name']);
        $this->assertSame('./', $manifest['start_url']);
        $this->assertSame('./', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('rtl', $manifest['dir']);
        $this->assertSame(['images/logo-192.png', 'images/logo-512.png'], array_column($manifest['icons'], 'src'));
    }

    public function test_public_and_admin_surfaces_reference_the_new_logo(): void
    {
        foreach (['components/logo.blade.php', 'components/application-logo.blade.php', 'layouts/admin.blade.php'] as $view) {
            $contents = file_get_contents(resource_path('views/'.$view));

            $this->assertStringContainsString('images/logo.png', $contents, $view);
            $this->assertStringNotContainsString('images/logo-header.svg', $contents, $view);
        }
    }
}
