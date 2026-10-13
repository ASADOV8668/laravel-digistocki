<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\SellerStore;
use App\Models\User;
use App\Notifications\PendingListingNotification;
use App\Notifications\PendingStorefrontNotification;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\County;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseTwoHundredFiftyFourNotificationCenterTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_new_store_application_notifies_all_admins_with_a_database_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['mobile' => '09120001254']);
        [$province, $city] = $this->location('استان اعلان فروشگاه', 'شهر اعلان فروشگاه');

        $this->actingAs($user)->put(route('storefront.update'), [
            'first_name' => 'کاربر',
            'last_name' => 'فروشگاه',
            'national_id' => '0067123457',
            'name' => 'فروشگاه اعلان',
            'slug' => 'notification-store',
            'contact_phone' => $user->mobile,
            'business_license_number' => 'LIC-NOTIFICATION',
            'business_license_expires_at_year' => '1405',
            'business_license_expires_at_month' => '12',
            'business_license_expires_at_day' => '29',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'آدرس اعلان',
            'business_license' => UploadedFile::fake()->create('license.jpg', 10, 'image/jpeg'),
        ])->assertRedirect();

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame(PendingStorefrontNotification::class, $notification->type);
        $this->assertSame('فروشگاه منتظر تأیید است', $notification->data['title']);
        $this->assertSame(SellerStore::firstOrFail()->id, $notification->data['store_id']);
        $this->assertNull($notification->read_at);
    }

    public function test_pending_user_listing_notifies_all_admins_and_links_to_admin_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $this->storeApplication($user);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی منتظر بررسی مدیر',
            'price' => 25000000,
            'attributes' => $this->requiredAttributeValues($model),
        ])->assertRedirect();

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame(PendingListingNotification::class, $notification->type);
        $this->assertSame('آگهی منتظر تأیید است', $notification->data['title']);
        $this->assertStringContainsString('/admin/listings/', $notification->data['url']);
        $this->assertNull($notification->read_at);
    }

    public function test_admin_notification_center_shows_badge_supports_read_all_and_delete_all(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createNotification($admin, false);
        $this->createNotification($admin, false);

        $this->actingAs($admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('data-admin-notifications-link', false)
            ->assertSee('data-admin-unread-notifications-badge', false)
            ->assertSee('۲', false)
            ->assertSee('اعلان‌های مدیریت');

        $this->actingAs($admin)->patchJson(route('admin.notifications.read-all'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);
        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());

        $this->actingAs($admin)->deleteJson(route('admin.notifications.destroy-all'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);
        $this->assertSame(0, $admin->fresh()->notifications()->count());
    }

    public function test_user_can_delete_all_database_notifications(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user, false);
        $this->createNotification($user, true);

        $this->actingAs($user)->deleteJson(route('notifications.destroy-all'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }

    /** @return array{0: Province, 1: City} */
    private function location(string $provinceName, string $cityName): array
    {
        $province = Province::create(['name' => $provinceName, 'tel_prefix' => '98']);
        $county = County::create(['name' => $provinceName.' شهرستان', 'province_id' => $province->id]);
        $city = City::create(['name' => $cityName, 'province_id' => $province->id, 'county_id' => $county->id]);

        return [$province, $city];
    }

    private function createNotification(User $user, bool $read): DatabaseNotification
    {
        $notification = new DatabaseNotification([
            'id' => (string) Str::uuid(),
            'type' => PendingListingNotification::class,
            'data' => ['title' => 'اعلان تستی', 'message' => 'پیام تستی'],
            'read_at' => $read ? now() : null,
        ]);
        $notification->notifiable()->associate($user);
        $notification->save();

        return $notification;
    }
}
