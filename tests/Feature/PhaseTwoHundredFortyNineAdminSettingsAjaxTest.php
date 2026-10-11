<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFortyNineAdminSettingsAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_system_settings_with_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->putJson(route('admin.settings.update'), [
                'site_title' => 'دیجی استوکی جدید',
                'page_title_prefix' => 'دیجی استوکی جدید',
                'title_separator' => '—',
                'max_image_upload_mb' => 8,
                'max_image_upload_count' => 7,
                'support_office_address' => 'آدرس جدید',
                'support_email' => 'support@example.com',
                'support_phone' => '02112345678',
                'listings_enabled' => true,
                'allow_contact_price' => false,
                'sms_mode' => 'test',
                'registration_mode' => 'mobile',
                'otp_expiry_minutes' => 8,
                'listing_duplicate_cooldown_hours' => 48,
            ])
            ->assertOk()
            ->assertJson(['message' => 'تنظیمات سیستم ذخیره شد.']);

        $this->assertDatabaseHas('options', ['key' => 'site_title', 'value' => 'دیجی استوکی جدید']);
        $this->assertDatabaseHas('options', ['key' => 'max_image_upload_count', 'value' => '7']);
        $this->assertDatabaseHas('options', ['key' => 'allow_contact_price', 'value' => '0']);
    }

    public function test_ajax_settings_validation_returns_field_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->putJson(route('admin.settings.update'), [
                'site_title' => '',
                'title_separator' => '',
                'max_image_upload_mb' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['site_title', 'title_separator', 'max_image_upload_mb']);
    }
}
