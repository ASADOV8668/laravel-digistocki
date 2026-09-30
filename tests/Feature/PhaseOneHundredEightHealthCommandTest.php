<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PhaseOneHundredEightHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_command_reports_all_deployment_dependencies_as_healthy(): void
    {
        $exitCode = Artisan::call('app:health', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($payload['ok']);
        $this->assertSame([
            'database' => true,
            'storage' => true,
            'storage_link' => true,
            'build_manifest' => true,
            'apache_front_controller' => true,
        ], $payload['checks']);
    }
}
