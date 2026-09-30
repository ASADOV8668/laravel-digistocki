<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PhaseOneHundredTwelveHealthModeTest extends TestCase
{
    public function test_health_json_output_identifies_strict_and_portable_modes(): void
    {
        $strictExitCode = Artisan::call('app:health', ['--json' => true]);
        $strictPayload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $portableExitCode = Artisan::call('app:health', [
            '--json' => true,
            '--skip-deployment-assets' => true,
        ]);
        $portablePayload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $strictExitCode);
        $this->assertSame('strict', $strictPayload['mode']);
        $this->assertSame([], $strictPayload['skipped']);
        $this->assertSame(0, $portableExitCode);
        $this->assertSame('portable', $portablePayload['mode']);
        $this->assertSame(['storage_link', 'build_manifest'], $portablePayload['skipped']);
        $this->assertTrue($portablePayload['ok']);
    }
}
