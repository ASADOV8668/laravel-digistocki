<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseFortyEightPwaAssetsTest extends TestCase
{
    public function test_manifest_declares_installable_rtl_brand_icons(): void
    {
        $manifestPath = public_path('manifest.json');
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('rtl', $manifest['dir']);
        $this->assertSame('fa', $manifest['lang']);
        $this->assertSame(['192x192', '512x512'], array_column($manifest['icons'], 'sizes'));
        $this->assertFileExists(public_path('images/logo-192.svg'));
        $this->assertFileExists(public_path('images/logo-512.svg'));
    }

    public function test_public_and_guest_layouts_expose_manifest_and_favicon(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('rel="icon"', false)
            ->assertSee('apple-mobile-web-app-capable', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('rel="icon"', false);
    }
}
