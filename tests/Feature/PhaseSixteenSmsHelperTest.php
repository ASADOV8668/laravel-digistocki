<?php

namespace Tests\Feature;

use App\Helpers\SmsHelper;
use App\Models\SystemOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PhaseSixteenSmsHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_helper_logs_without_sending_in_test_mode(): void
    {
        Log::shouldReceive('info')->once()->with('SMS test mode: message was not sent', Mockery::on(fn (array $context) => $context['mobile'] === '09121234567'
            && $context['message'] === 'کد تست ۱۲۳۴۵۶'));

        $this->assertTrue(SmsHelper::send('09121234567', 'کد تست ۱۲۳۴۵۶'));
    }

    public function test_live_mode_uses_the_replaceable_real_sender_stub(): void
    {
        Log::shouldReceive('warning')->once()->with('SMS live mode selected, but provider is not configured yet', Mockery::type('array'));
        SystemOption::create(['key' => 'sms_mode', 'value' => 'live', 'type' => 'string']);

        $this->assertFalse(SmsHelper::send('09121234567', 'کد واقعی'));
    }

    public function test_admin_can_change_sms_mode_from_system_settings(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_title' => 'دیجی استوک',
            'page_title_prefix' => 'دیجی استوک',
            'title_separator' => '|',
            'max_image_upload_mb' => 5,
            'sms_mode' => 'live',
            'listings_enabled' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('options', ['key' => 'sms_mode', 'value' => 'live']);
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('وب‌سرویس Pattern آی‌پی‌پنل');
    }
}
