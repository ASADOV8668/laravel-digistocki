<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseFiftyServiceWorkerTest extends TestCase
{
    public function test_service_worker_caches_the_shell_and_supports_offline_navigation(): void
    {
        $serviceWorker = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString('digistocki-assets-v5', $serviceWorker);
        $this->assertStringContainsString('cache.addAll', $serviceWorker);
        $this->assertStringContainsString("event.request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString('caches.delete', $serviceWorker);
        $this->assertStringContainsString('self.skipWaiting', $serviceWorker);
    }
}
