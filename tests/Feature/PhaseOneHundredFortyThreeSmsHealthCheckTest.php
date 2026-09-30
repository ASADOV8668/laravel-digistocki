<?php

namespace Tests\Feature;

use App\Models\SystemOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PhaseOneHundredFortyThreeSmsHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_sms_requires_a_provider_url_in_health_check(): void
    {
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);
        config()->set('services.sms.url', null);

        $exitCode = Artisan::call('app:health', ['--json' => true, '--skip-deployment-assets' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $exitCode);
        $this->assertFalse($payload['ok']);
        $this->assertFalse($payload['checks']['sms_provider']);
    }

    public function test_test_sms_mode_does_not_block_health_check_without_provider_credentials(): void
    {
        config()->set('services.sms.url', null);

        $exitCode = Artisan::call('app:health', ['--json' => true, '--skip-deployment-assets' => true]);
        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertArrayNotHasKey('sms_provider', $payload['checks']);
    }
}
