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
        config()->set('services.sms.url', 'https://edge.ippanel.com/v1/api/send');
        config()->set('services.sms.authorization', 'provider-token');
        config()->set('services.sms.pattern_code', 'pattern-code');
        config()->set('services.sms.sending_type', 'pattern');
        config()->set('services.sms.sender', '+983000505');
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);
        Http::fake(['https://edge.ippanel.com/*' => Http::response(['meta' => ['status' => true, 'message_code' => '200-1']], 200)]);

        $this->assertTrue(SmsHelper::send('09121234567', 'کد ۱۲۳۴۵۶', ['otp' => '123456']));
        Http::assertSent(function ($request) {
            return $request->url() === 'https://edge.ippanel.com/v1/api/send'
                && $request->hasHeader('Authorization', 'provider-token')
                && $request->hasHeader('Content-Type', 'application/json')
                && $request['sending_type'] === 'pattern'
                && $request['from_number'] === '+983000505'
                && $request['code'] === 'pattern-code'
                && $request['recipients'] === ['09121234567']
                && $request['params']['otp'] === '123456';
        });
    }

    public function test_live_sms_fails_safely_when_provider_is_not_configured(): void
    {
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);

        $this->assertFalse(SmsHelper::send('09121234567', 'کد تست'));
    }

    public function test_live_sms_fails_when_ipanel_reports_unsuccessful_delivery(): void
    {
        config()->set('services.sms.url', 'https://edge.ippanel.com/v1/api/send');
        config()->set('services.sms.authorization', 'provider-token');
        config()->set('services.sms.pattern_code', 'pattern-code');
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);
        Http::fake(['https://edge.ippanel.com/*' => Http::response([
            'meta' => ['status' => false, 'message' => 'ارسال ناموفق'],
        ], 200)]);

        $this->assertFalse(SmsHelper::send('09121234567', 'کد 12345', ['otp' => '12345']));
    }
}
