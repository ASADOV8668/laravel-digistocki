<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredThirtyEightFlowbiteFocusTargetsTest extends TestCase
{
    public function test_public_flowbite_overlays_and_navigation_have_visible_focus_targets(): void
    {
        foreach ([
            resource_path('views/components/modal.blade.php'),
            resource_path('views/components/sidebar.blade.php'),
            resource_path('views/listings/index.blade.php'),
        ] as $path) {
            $source = File::get($path);

            $this->assertStringContainsString('focus:outline-none focus:ring-4', $source);
        }
    }
}
