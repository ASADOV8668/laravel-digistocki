<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseEightyFiveFaviconLinksTest extends TestCase
{
    public function test_application_layouts_reference_raster_favicon_assets(): void
    {
        foreach (['app.blade.php', 'guest.blade.php', 'admin.blade.php'] as $layout) {
            $contents = file_get_contents(resource_path('views/layouts/'.$layout));

            $this->assertStringContainsString("asset('favicon.ico')", $contents, $layout);
            $this->assertStringContainsString("asset('images/logo-192.png')", $contents, $layout);
            $this->assertStringNotContainsString('rel="icon" type="image/svg+xml"', $contents, $layout);
        }
    }
}
