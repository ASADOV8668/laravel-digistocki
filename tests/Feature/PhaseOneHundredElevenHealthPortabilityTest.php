<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PhaseOneHundredElevenHealthPortabilityTest extends TestCase
{
    public function test_health_command_exposes_an_explicit_portable_mode_for_fresh_checkouts(): void
    {
        $exitCode = Artisan::call('app:health', [
            '--json' => true,
            '--skip-deployment-assets' => true,
        ]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($payload['ok']);
        $this->assertTrue($payload['checks']['storage']);
        $this->assertTrue($payload['checks']['apache_front_controller']);
    }
}
