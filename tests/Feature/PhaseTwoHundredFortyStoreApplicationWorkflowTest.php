<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use App\Support\PersianDate;
use App\Support\SocialHandle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class PhaseTwoHundredFortyStoreApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_application_requires_the_new_identity_and_license_fields(): void
    {
        $user = User::factory()->create(['mobile' => '09120001111']);

        $this->actingAs($user)->put(route('storefront.update'), [
            'name' => 'فروشگاه تست',
            'slug' => 'test-store',
            'contact_phone' => '09120001111',
        ])->assertSessionHasErrors(['first_name', 'last_name', 'national_id', 'business_license_number', 'business_license_expires_at', 'province_id', 'city_id', 'address', 'logo', 'business_license']);

        $this->assertDatabaseMissing('seller_stores', ['user_id' => $user->id]);
    }

    public function test_store_application_requires_a_logo_and_rejects_past_expiry_dates(): void
    {
        $user = User::factory()->create(['mobile' => '09120001222']);
        [$province, $city] = $this->location('استان لوگو', 'شهر لوگو');
        $validPayload = [
            'first_name' => 'کاربر',
            'last_name' => 'لوگو',
            'national_id' => '0067123457',
            'name' => 'فروشگاه لوگو',
            'slug' => 'logo-store',
            'contact_phone' => $user->mobile,
            'business_license_number' => 'LIC-LOGO',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس لوگو',
            'business_license' => UploadedFile::fake()->create('license.jpg', 10, 'image/jpeg'),
        ];
        [$pastYear, $pastMonth, $pastDay] = array_map('intval', explode('/', PersianDate::format(now()->subDay())));

        $this->actingAs($user)->put(route('storefront.update'), [
            ...$validPayload,
            'business_license_expires_at_year' => $pastYear,
            'business_license_expires_at_month' => $pastMonth,
            'business_license_expires_at_day' => $pastDay,
        ])->assertSessionHasErrors(['logo', 'business_license_expires_at']);

        $this->assertDatabaseMissing('seller_stores', ['user_id' => $user->id]);
    }

    public function test_pending_storefront_hides_the_edit_form_until_management_decides(): void
    {
        $user = User::factory()->create(['mobile' => '09120001010']);
        SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'pending-store',
            'name' => 'فروشگاه در انتظار',
            'first_name' => 'کاربر',
            'last_name' => 'در انتظار',
            'contact_phone' => $user->mobile,
            'approval_status' => 'pending',
            'is_enabled' => false,
        ]);

        $this->actingAs($user)->get(route('storefront.edit'))
            ->assertOk()
            ->assertSee('فرم ویرایش در دسترس نیست')
            ->assertDontSee('name="first_name"', false)
            ->assertSee('فروشگاه در انتظار');
    }

    public function test_admin_can_open_the_full_storefront_and_user_details_from_the_storefront_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create([
            'name' => 'فرشید نعمتی',
            'email' => 'farshid@example.test',
            'mobile' => '09120001020',
            'national_id' => '0012345678',
        ]);
        $store = SellerStore::create([
            'user_id' => $owner->id,
            'slug' => 'full-details-store',
            'name' => 'فروشگاه اطلاعات کامل',
            'first_name' => 'فرشید',
            'last_name' => 'نعمتی',
            'national_id' => '0012345678',
            'contact_phone' => '09120001020',
            'address' => 'تهران، خیابان اصلی',
            'business_license_number' => 'LIC-1020',
            'instagram_url' => 'digistocki',
            'approval_status' => 'pending',
            'is_enabled' => false,
        ]);

        $this->actingAs($admin)->get(route('admin.storefronts.index'))
            ->assertOk()
            ->assertSee(route('admin.storefronts.show', $store), false)
            ->assertSee($owner->name);

        $this->actingAs($admin)->get(route('admin.storefronts.show', $store))
            ->assertOk()
            ->assertSee($owner->name)
            ->assertSee($owner->email)
            ->assertSee($owner->mobile)
            ->assertSee($store->national_id)
            ->assertSee($store->business_license_number)
            ->assertSee($store->address)
            ->assertSee($store->instagram_url);
    }

    public function test_admin_approval_and_rejection_create_database_notifications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['mobile' => '09120002222']);
        $store = SellerStore::create([
            'user_id' => $owner->id,
            'slug' => 'workflow-store',
            'name' => 'فروشگاه گردش کار',
            'approval_status' => 'pending',
            'is_enabled' => false,
        ]);

        $this->actingAs($admin)->patch(route('admin.storefronts.approve', $store))
            ->assertSessionHas('status', 'فروشگاه با موفقیت تأیید و فعال شد.');
        $this->assertTrue($store->fresh()->isPubliclyEnabled());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id, 'type' => 'App\\Notifications\\StorefrontStatusNotification']);

        $this->actingAs($admin)->patch(route('admin.storefronts.reject', $store), ['rejection_reason' => 'تصویر جواز خوانا نیست.'])
            ->assertSessionHas('status', 'درخواست فروشگاه رد شد و علت برای کاربر ثبت گردید.');
        $this->assertSame('rejected', $store->fresh()->approval_status);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id, 'type' => 'App\\Notifications\\StorefrontStatusNotification']);
    }

    public function test_persian_license_date_parts_are_combined_before_persistence(): void
    {
        $user = User::factory()->create(['mobile' => '09120003333']);
        $province = Province::create(['name' => 'استان تاریخ', 'tel_prefix' => '98']);
        $county = County::create(['name' => 'شهرستان تاریخ', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر تاریخ', 'province_id' => $province->id, 'county_id' => $county->id]);

        $response = $this->actingAs($user)->put(route('storefront.update'), [
            'first_name' => 'کاربر',
            'last_name' => 'تاریخ',
            'national_id' => '0067123457',
            'name' => 'فروشگاه تاریخ',
            'slug' => 'date-store',
            'contact_phone' => '09120003333',
            'business_license_number' => 'LIC-DATE',
            'business_license_expires_at_year' => '1405',
            'business_license_expires_at_month' => '12',
            'business_license_expires_at_day' => '29',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس تاریخ',
            'business_license' => UploadedFile::fake()->create('license-date.jpg', 10, 'image/jpeg'),
            'logo' => UploadedFile::fake()->create('logo-date.jpg', 10, 'image/jpeg'),
        ]);

        $response->assertRedirect();
        $this->assertSame(PersianDate::parseDate('1405/12/29')?->toDateString(), SellerStore::firstOrFail()->business_license_expires_at->toDateString());
        $this->assertSeeDateSelectOn($this->actingAs($user)->get(route('storefront.edit')));
    }

    public function test_empty_license_date_defaults_to_today_and_limits_years(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['mobile' => '09120004444']);
        $currentYear = (int) PersianDate::format(now(), 'Y');

        $this->actingAs($admin)->get(route('admin.storefronts.edit', $user))
            ->assertOk()
            ->assertSee('name="business_license_expires_at_year"', false)
            ->assertDontSee('value="1350"', false)
            ->assertSee('value="'.$currentYear.'" selected', false)
            ->assertSee('value="'.($currentYear + 10).'"', false)
            ->assertDontSee('value="'.($currentYear + 11).'"', false);
    }

    public function test_license_year_after_current_year_is_rejected(): void
    {
        $user = User::factory()->create(['mobile' => '09120005555']);

        $this->actingAs($user)->put(route('storefront.update'), [
            'first_name' => 'کاربر',
            'last_name' => 'سال',
            'national_id' => '0067123457',
            'name' => 'فروشگاه سال',
            'slug' => 'year-store',
            'contact_phone' => '09120005555',
            'business_license_number' => 'LIC-YEAR',
            'business_license_expires_at_year' => (int) PersianDate::format(now(), 'Y') + 11,
            'business_license_expires_at_month' => '1',
            'business_license_expires_at_day' => '1',
        ])->assertSessionHasErrors('business_license_expires_at_year');

        $this->assertDatabaseMissing('seller_stores', ['user_id' => $user->id]);
    }

    public function test_social_accounts_store_handles_and_render_as_platform_links(): void
    {
        $user = User::factory()->create(['mobile' => '09120006666']);
        $province = Province::create(['name' => 'استان شبکه', 'tel_prefix' => '97']);
        $county = County::create(['name' => 'شهرستان شبکه', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر شبکه', 'province_id' => $province->id, 'county_id' => $county->id]);

        $this->actingAs($user)->put(route('storefront.update'), [
            'first_name' => 'کاربر',
            'last_name' => 'شبکه',
            'national_id' => '0067123457',
            'name' => 'فروشگاه شبکه',
            'slug' => 'social-store',
            'contact_phone' => '09120006666',
            'business_license_number' => 'LIC-SOCIAL',
            'business_license_expires_at_year' => '1405',
            'business_license_expires_at_month' => '12',
            'business_license_expires_at_day' => '29',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس شبکه',
            'business_license' => UploadedFile::fake()->create('license-social.jpg', 10, 'image/jpeg'),
            'logo' => UploadedFile::fake()->create('logo-social.jpg', 10, 'image/jpeg'),
            'instagram_url' => '@digistocki',
            'telegram_url' => 'https://t.me/digistocki',
            'whatsapp_url' => '989120000000',
            'bale_url' => 'digistocki',
            'rubika_url' => 'rubika_shop',
        ])->assertRedirect();

        $store = SellerStore::firstOrFail();
        $this->assertSame('digistocki', $store->instagram_url);
        $this->assertSame('digistocki', $store->telegram_url);
        $this->assertSame('989120000000', $store->whatsapp_url);
        $this->assertSame('digistocki', $store->bale_url);
        $this->assertSame('rubika_shop', $store->rubika_url);

        $store->update(['approval_status' => 'approved', 'is_enabled' => true]);
        $this->actingAs($user)->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee(SocialHandle::url($store->instagram_url, 'instagram'), false)
            ->assertSee(SocialHandle::url($store->telegram_url, 'telegram'), false)
            ->assertSee(SocialHandle::url($store->whatsapp_url, 'whatsapp'), false);
    }

    public function test_admin_can_save_a_persian_storefront_address(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['mobile' => '09120007777']);

        $this->actingAs($admin)->put(route('admin.storefronts.update', $owner), [
            'approval_status' => 'pending',
            'name' => 'فروشگاه مدیریت',
            'slug' => 'admin-persian-address',
            'address' => 'مازندران، بابلسر، خیابان اصلی، پلاک ۱۲',
            'logo' => UploadedFile::fake()->create('admin-logo.jpg', 10, 'image/jpeg'),
            'business_license' => UploadedFile::fake()->create('admin-license.jpg', 10, 'image/jpeg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('seller_stores', [
            'user_id' => $owner->id,
            'address' => 'مازندران، بابلسر، خیابان اصلی، پلاک ۱۲',
        ]);
    }

    public function test_storefront_forms_prefill_whatsapp_from_the_user_mobile(): void
    {
        $user = User::factory()->create(['mobile' => '09120008888']);
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['mobile' => '09120009999']);

        $this->actingAs($user)->get(route('storefront.edit'))
            ->assertOk()
            ->assertSee('value="989120008888"', false);

        $this->actingAs($admin)->get(route('admin.storefronts.edit', $owner))
            ->assertOk()
            ->assertSee('value="989120009999"', false);
    }

    public function test_storefront_edit_forms_show_existing_images_and_remove_controls(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('stores/current-logo.webp', 'logo');
        Storage::disk('public')->put('business-licenses/current-license.webp', 'license');

        $user = User::factory()->create(['mobile' => '09120001030']);
        $admin = User::factory()->create(['role' => 'admin']);
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'image-preview-store',
            'name' => 'فروشگاه پیش‌نمایش تصاویر',
            'logo_path' => 'stores/current-logo.webp',
            'business_license_path' => 'business-licenses/current-license.webp',
            'approval_status' => 'approved',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)->get(route('storefront.edit'))
            ->assertOk()
            ->assertSee(asset('storage/'.$store->logo_path), false)
            ->assertSee(asset('storage/'.$store->business_license_path), false)
            ->assertSee('name="remove_logo"', false)
            ->assertSee('name="remove_business_license"', false);

        $this->actingAs($admin)->get(route('admin.storefronts.edit', $user))
            ->assertOk()
            ->assertSee(asset('storage/'.$store->logo_path), false)
            ->assertSee(asset('storage/'.$store->business_license_path), false)
            ->assertSee('name="remove_logo"', false)
            ->assertSee('name="remove_business_license"', false);
    }

    public function test_storefront_image_controls_can_remove_existing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('stores/remove-logo.webp', 'logo');
        Storage::disk('public')->put('business-licenses/remove-license.webp', 'license');

        $user = User::factory()->create(['mobile' => '09120001040']);
        $province = Province::create(['name' => 'استان حذف', 'tel_prefix' => '96']);
        $county = County::create(['name' => 'شهرستان حذف', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر حذف', 'province_id' => $province->id, 'county_id' => $county->id]);
        $store = SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'remove-image-store',
            'name' => 'فروشگاه حذف تصاویر',
            'first_name' => 'کاربر',
            'last_name' => 'تصویر',
            'national_id' => '0067123457',
            'contact_phone' => $user->mobile,
            'business_license_number' => 'LIC-1040',
            'business_license_expires_at' => '2030-01-01',
            'logo_path' => 'stores/remove-logo.webp',
            'business_license_path' => 'business-licenses/remove-license.webp',
            'approval_status' => 'approved',
            'is_enabled' => true,
        ]);

        $this->actingAs($user)->put(route('storefront.update'), [
            'first_name' => 'کاربر',
            'last_name' => 'تصویر',
            'national_id' => '0067123457',
            'name' => $store->name,
            'contact_phone' => $user->mobile,
            'business_license_number' => 'LIC-1040',
            'business_license_expires_at_year' => '1405',
            'business_license_expires_at_month' => '12',
            'business_license_expires_at_day' => '29',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس حذف',
            'remove_logo' => 1,
            'remove_business_license' => 1,
            'logo' => UploadedFile::fake()->create('new-logo.jpg', 10, 'image/jpeg'),
            'business_license' => UploadedFile::fake()->create('new-license.jpg', 10, 'image/jpeg'),
        ])->assertRedirect();

        Storage::disk('public')->assertMissing('stores/remove-logo.webp');
        Storage::disk('public')->assertMissing('business-licenses/remove-license.webp');
        $this->assertNotSame('stores/remove-logo.webp', $store->refresh()->logo_path);
        $this->assertNotSame('business-licenses/remove-license.webp', $store->business_license_path);
    }

    public function test_deleting_a_storefront_removes_its_stored_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('stores/deleted-logo.webp', 'logo');
        Storage::disk('public')->put('business-licenses/deleted-license.webp', 'license');

        $store = SellerStore::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'deleted-image-store',
            'name' => 'فروشگاه حذف‌شده',
            'logo_path' => 'stores/deleted-logo.webp',
            'business_license_path' => 'business-licenses/deleted-license.webp',
        ]);

        $store->delete();

        Storage::disk('public')->assertMissing('stores/deleted-logo.webp');
        Storage::disk('public')->assertMissing('business-licenses/deleted-license.webp');
    }

    private function assertSeeDateSelectOn($response): void
    {
        $response->assertOk()
            ->assertSee('name="business_license_expires_at_day"', false)
            ->assertSee('name="business_license_expires_at_month"', false)
            ->assertSee('name="business_license_expires_at_year"', false)
            ->assertDontSee('value="1350"', false)
            ->assertSee('value="'.(int) PersianDate::format(now(), 'Y').'"', false)
            ->assertDontSee('data-persian-datepicker', false);
    }

    /** @return array{0: Province, 1: City} */
    private function location(string $provinceName, string $cityName): array
    {
        $province = Province::create(['name' => $provinceName, 'tel_prefix' => '98']);
        $county = County::create(['name' => $provinceName.' شهرستان', 'province_id' => $province->id]);
        $city = City::create(['name' => $cityName, 'province_id' => $province->id, 'county_id' => $county->id]);

        return [$province, $city];
    }
}
