<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredNinetyOneImageLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_critical_listing_images_use_lazy_async_loading(): void
    {
        foreach ([
            resource_path('views/dashboard.blade.php'),
            resource_path('views/welcome.blade.php'),
            resource_path('views/listings/show.blade.php'),
            resource_path('views/listings/edit.blade.php'),
            resource_path('views/admin/listings/index.blade.php'),
            resource_path('views/admin/listings/form.blade.php'),
        ] as $path) {
            $template = file_get_contents($path);

            $this->assertNotFalse($template);
            $this->assertStringContainsString('loading="lazy"', $template);
            $this->assertStringContainsString('decoding="async"', $template);
        }
    }
}
