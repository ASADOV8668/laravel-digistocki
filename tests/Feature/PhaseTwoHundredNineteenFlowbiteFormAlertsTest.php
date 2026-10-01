<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredNineteenFlowbiteFormAlertsTest extends TestCase
{
    public function test_public_form_views_use_the_shared_flowbite_alert_component(): void
    {
        foreach ([
            'components/auth-session-status.blade.php',
            'auth/verify-email.blade.php',
            'listings/create.blade.php',
            'listings/edit.blade.php',
            'storefront/edit.blade.php',
        ] as $view) {
            $source = file_get_contents(resource_path("views/{$view}"));

            $this->assertStringContainsString('flowbite-alert', $source);
        }
    }
}
