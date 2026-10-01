<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyOneFlowbiteCardRadiusTest extends TestCase
{
    public function test_public_cards_use_flowbite_standard_radius(): void
    {
        $paths = [
            resource_path('views/welcome.blade.php'),
            resource_path('views/listings/show.blade.php'),
            resource_path('views/storefront/show.blade.php'),
            resource_path('views/storefront/disabled.blade.php'),
            resource_path('views/support/index.blade.php'),
            resource_path('views/errors/404.blade.php'),
            resource_path('views/errors/500.blade.php'),
        ];

        foreach ($paths as $path) {
            $this->assertStringNotContainsString('rounded-[2rem]', file_get_contents($path), $path);
        }
    }
}
