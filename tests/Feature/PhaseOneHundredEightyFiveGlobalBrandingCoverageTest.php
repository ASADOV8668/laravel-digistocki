<?php

namespace Tests\Feature;

use App\Models\SystemOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEightyFiveGlobalBrandingCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_not_found_page_uses_the_configured_site_title(): void
    {
        SystemOption::create(['key' => 'site_title', 'value' => 'برند آزمایشی', 'type' => 'string']);

        $this->get('/route-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('<title>صفحه پیدا نشد | برند آزمایشی</title>', false);
    }

    public function test_error_and_storefront_templates_do_not_hardcode_the_default_brand(): void
    {
        foreach ([
            resource_path('views/errors/404.blade.php'),
            resource_path('views/errors/500.blade.php'),
            resource_path('views/storefront/show.blade.php'),
        ] as $path) {
            $template = file_get_contents($path);

            $this->assertNotFalse($template);
            $this->assertStringContainsString('SystemOptions::class', $template);
            $this->assertStringNotContainsString(' | دیجی استوک', $template);
        }
    }
}
