<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredTenSecurityHeadersTest extends TestCase
{
    public function test_authenticated_and_admin_pages_keep_resource_isolation_headers(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->assertHeader('X-DNS-Prefetch-Control', 'off');
    }
}
