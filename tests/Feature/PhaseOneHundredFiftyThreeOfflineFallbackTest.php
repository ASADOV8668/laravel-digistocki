<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFiftyThreeOfflineFallbackTest extends TestCase
{
    public function test_branded_offline_page_is_available_to_the_service_worker(): void
    {
        $offlinePage = file_get_contents(public_path('offline.html'));

        $this->assertNotFalse($offlinePage);
        $this->assertStringContainsString('اتصال برقرار نیست', $offlinePage);
        $this->assertStringContainsString('./images/logo-192.png', $offlinePage);
        $this->assertStringContainsString('window.location.reload()', $offlinePage);
    }

    public function test_service_worker_caches_and_uses_the_offline_fallback(): void
    {
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertNotFalse($serviceWorker);
        $this->assertStringContainsString("'./offline.html'", $serviceWorker);
        $this->assertStringContainsString("new URL('./offline.html', self.registration.scope)", $serviceWorker);
        $this->assertStringNotContainsString("caches.match(new URL('./', self.registration.scope))", $serviceWorker);
    }
}
