<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use App\Support\PersianDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        ])->assertSessionHasErrors(['first_name', 'last_name', 'national_id', 'business_license_number', 'business_license_expires_at', 'province_id', 'city_id', 'address', 'business_license']);

        $this->assertDatabaseMissing('seller_stores', ['user_id' => $user->id]);
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
        ]);

        $response->assertRedirect();
        $this->assertSame(PersianDate::parseDate('1405/12/29')?->toDateString(), SellerStore::firstOrFail()->business_license_expires_at->toDateString());
        $this->assertSeeDateSelectOn($this->actingAs($user)->get(route('storefront.edit')));
    }

    private function assertSeeDateSelectOn($response): void
    {
        $response->assertOk()
            ->assertSee('name="business_license_expires_at_day"', false)
            ->assertSee('name="business_license_expires_at_month"', false)
            ->assertSee('name="business_license_expires_at_year"', false)
            ->assertSee('value="1350"', false)
            ->assertSee('value="'.(int) PersianDate::format(now(), 'Y').'"', false)
            ->assertDontSee('data-persian-datepicker', false);
    }
}
