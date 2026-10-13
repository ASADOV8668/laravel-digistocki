<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class PhaseOneHundredSixtyOneStorefrontManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_storefront_with_an_english_slug(): void
    {
        $user = User::factory()->create(['mobile' => '09120001111']);
        $province = Province::create(['name' => 'استان تست', 'tel_prefix' => '99']);
        $county = County::create(['name' => 'شهرستان تست', 'province_id' => $province->id]);
        $city = City::create(['name' => 'شهر تست', 'province_id' => $province->id, 'county_id' => $county->id]);

        $this->actingAs($user)->get(route('storefront.edit'))
            ->assertOk()
            ->assertSee('ثبت فروشگاه');

        $this->actingAs($user)->put(route('storefront.update'), [
            'store_enabled' => 1,
            'first_name' => 'کاربر',
            'last_name' => 'تست',
            'national_id' => '۰۰۶۷۱۲۳۴۵۷',
            'name' => 'فروشگاه رضایی',
            'slug' => 'reza-mobile',
            'contact_phone' => '۰۹۱۲۰۰۰۱۱۱۱',
            'business_license_number' => 'LIC-۰۰۱',
            'business_license_expires_at' => '۱۴۰۵/۱۲/۲۹',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس تست',
            'business_license' => UploadedFile::fake()->create('license.jpg', 10, 'image/jpeg'),
        ])->assertRedirect()->assertSessionHas('status', 'درخواست فروشگاه شما ثبت شد و پس از بررسی مدیریت اعلام نتیجه خواهد شد.');

        $store = SellerStore::where('user_id', $user->id)->firstOrFail();
        $this->assertFalse($store->is_enabled);
        $this->assertSame('reza-mobile', $store->slug);
        $this->assertSame('09120001111', $store->contact_phone);
        $this->assertSame('LIC-001', $store->business_license_number);
        $this->assertSame('0067123457', $store->national_id);
        $this->assertSame('آدرس تست', $store->address);
    }

    public function test_existing_storefront_slug_cannot_be_changed_by_the_owner(): void
    {
        $user = User::factory()->create();
        SellerStore::create(['user_id' => $user->id, 'slug' => 'locked-store', 'name' => 'غرفه قفل‌شده']);

        $this->actingAs($user)->put(route('storefront.update'), [
            'store_enabled' => 1,
            'name' => 'غرفه جدید',
            'slug' => 'new-store',
        ])->assertSessionHasErrors('slug');

        $this->assertSame('locked-store', $user->storefront->fresh()->slug);
    }

    public function test_invalid_store_slug_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('storefront.update'), [
            'store_enabled' => 1,
            'name' => 'غرفه نامعتبر',
            'slug' => 'غرفه فارسی',
        ])->assertSessionHasErrors('slug');
    }
}
