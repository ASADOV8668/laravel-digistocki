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

    public function test_service_worker_only_intercepts_cacheable_static_assets(): void
    {
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertNotFalse($serviceWorker);
        $this->assertStringContainsString('digistocki-shell-v4', $serviceWorker);
        $this->assertStringContainsString("['font', 'image', 'script', 'style']", $serviceWorker);
        $this->assertStringContainsString("if (!['font', 'image', 'script', 'style'].includes(event.request.destination)) return;", $serviceWorker);
    }

    public function test_public_layout_checks_for_a_new_service_worker_version(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertNotFalse($layout);
        $this->assertStringContainsString("navigator.serviceWorker.register('{{ asset('sw.js') }}')", $layout);
        $this->assertStringContainsString('.then((registration) => registration.update())', $layout);
    }
}
