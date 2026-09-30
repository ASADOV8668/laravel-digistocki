<?php

namespace Tests\Feature;

use App\Helpers\SmsHelper;
use App\Models\SystemOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhaseOneHundredFortyTwoSmsProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_sms_uses_the_configured_http_provider(): void
    {
        config()->set('services.sms.url', 'https://sms.example.test/send');
        config()->set('services.sms.token', 'provider-token');
        config()->set('services.sms.sender', 'Digistocki');
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);
        Http::fake(['https://sms.example.test/*' => Http::response(['accepted' => true], 200)]);

        $this->assertTrue(SmsHelper::send('09121234567', 'کد ۱۲۳۴۵۶'));
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sms.example.test/send'
                && $request->hasHeader('Authorization', 'Bearer provider-token')
                && $request['mobile'] === '09121234567'
                && $request['message'] === 'کد ۱۲۳۴۵۶'
                && $request['sender'] === 'Digistocki';
        });
    }

    public function test_live_sms_fails_safely_when_provider_is_not_configured(): void
    {
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);

        $this->assertFalse(SmsHelper::send('09121234567', 'کد تست'));
    }
}
