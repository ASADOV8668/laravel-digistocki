<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEightyOneLaravelNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_worker_caches_only_static_assets_and_never_replaces_laravel_navigation(): void
    {
        $serviceWorker = file_get_contents(public_path('sw.js'));

        $this->assertNotFalse($serviceWorker);
        $this->assertStringContainsString("const CACHE_NAME = 'digistocki-assets-v5';", $serviceWorker);
        $this->assertStringContainsString('const STATIC_ASSETS = [', $serviceWorker);
        $this->assertStringContainsString("if (event.request.mode === 'navigate')", $serviceWorker);
        $this->assertStringContainsString('fetch(event.request).catch', $serviceWorker);
        $this->assertStringContainsString("['font', 'image', 'script', 'style']", $serviceWorker);
        $this->assertStringNotContainsString("    './',", $serviceWorker);
        $this->assertStringNotContainsString('history.pushState', $serviceWorker);
        $this->assertStringNotContainsString('history.replaceState', $serviceWorker);
    }

    public function test_laravel_web_routes_remain_the_source_of_page_navigation(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertNotFalse($routes);
        $this->assertStringContainsString("Route::get('/', [HomeController::class, 'index'])->name('home');", $routes);
        $this->assertStringContainsString("Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');", $routes);
        $this->assertStringNotContainsString("Route::get('/listings/{listing:slug}'", $routes);
        $this->assertStringContainsString("Route::get('/', DashboardController::class)->name('admin.dashboard');", $routes);
    }
}
