<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseElevenSystemSettingsTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $this->admin->save();
    }

    public function test_admin_can_update_site_support_and_listing_settings(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('تنظیمات سیستم')
            ->assertSee('ارسال پیامک و OTP');

        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'site_title' => 'بازار موبایل',
            'page_title_prefix' => 'بازار',
            'title_separator' => '—',
            'max_image_upload_mb' => 8,
            'support_office_address' => 'تهران، خیابان تست',
            'support_email' => 'help@example.test',
            'support_phone' => '02112345678',
            'listings_enabled' => 1,
            'otp_expiry_minutes' => 7,
        ])->assertRedirect()->assertSessionHas('status', 'تنظیمات سیستم ذخیره شد.');

        $this->assertDatabaseHas('options', ['key' => 'site_title', 'value' => 'بازار موبایل']);
        $this->assertDatabaseHas('options', ['key' => 'max_image_upload_mb', 'value' => '8']);
        $this->assertDatabaseHas('options', ['key' => 'otp_expiry_minutes', 'value' => '7']);
        $this->assertDatabaseHas('options', ['key' => 'registration_mode', 'value' => 'mobile']);
        $this->get(route('home'))->assertOk()->assertSee('<title>بازار — خانه</title>', false);
        $this->get(route('support.index'))->assertOk()->assertSee('<title>بازار — ارتباط با پشتیبانی</title>', false)->assertSee('help@example.test')->assertSee('تهران، خیابان تست');
    }

    public function test_regular_user_cannot_access_settings_and_admin_can_disable_specific_user_listing_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();

        $this->actingAs($this->admin)->patch(route('admin.users.toggle-listing-permission', $user))->assertRedirect();
        $this->assertFalse($user->refresh()->can_post_listings);
        $this->actingAs($user)->get(route('listings.create'))->assertRedirect(route('home'))->assertSessionHas('status');
    }

    public function test_global_listing_switch_blocks_new_listing_creation(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'site_title' => 'دیجی استوک',
            'page_title_prefix' => 'دیجی استوک',
            'title_separator' => '|',
            'max_image_upload_mb' => 5,
            'listings_enabled' => 0,
        ])->assertRedirect();

        $this->actingAs(User::factory()->create())->get(route('listings.create'))->assertRedirect(route('home'));
    }

    public function test_configured_image_limit_is_used_by_listing_validation(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'site_title' => 'دیجی استوک',
            'page_title_prefix' => 'دیجی استوک',
            'title_separator' => '|',
            'max_image_upload_mb' => 1,
            'listings_enabled' => 1,
        ])->assertRedirect();

        $user = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'تصویر بزرگ',
            'price' => 1000000,
            'attributes' => $this->requiredAttributeValues($model),
            'images' => [UploadedFile::fake()->create('large.jpg', 1500, 'image/jpeg')],
        ])->assertSessionHasErrors('images.0');
    }
}
