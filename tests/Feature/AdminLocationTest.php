<?php

namespace Tests\Feature;

use App\Models\ProvinceDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;
use Tests\TestCase;

class AdminLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_rename_locations_without_changing_their_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('admin.locations.provinces.store'), ['name' => 'استان آزمایشی', 'tel_prefix' => '۰۲۱'])
            ->assertSessionHasNoErrors();

        $province = Province::query()->where('name', 'استان آزمایشی')->firstOrFail();
        $this->assertSame('021', $province->tel_prefix);
        $this->patch(route('admin.locations.provinces.update', $province), [
            'name' => 'استان ویرایش‌شده', 'tel_prefix' => '021',
        ])->assertSessionHasNoErrors();

        $cityData = ['name' => 'شهر آزمایشی', 'county' => 'شهرستان آزمایشی'];
        $this->post(route('admin.locations.cities.store', $province), $cityData)->assertSessionHasNoErrors();
        $this->post(route('admin.locations.cities.store', $province), $cityData)->assertSessionHasErrors('name');
        $city = City::query()->where('province_id', $province->id)->sole();

        $this->patch(route('admin.locations.cities.update', $city), ['name' => 'شهر ویرایش‌شده'])
            ->assertSessionHasNoErrors();

        $this->get(route('admin.locations.index', ['province' => $province->id]))
            ->assertOk()
            ->assertSee('استان ویرایش‌شده')
            ->assertSee('شهر ویرایش‌شده');

        $this->getJson(route('locations.provinces.cities', $province))
            ->assertExactJson([['id' => $city->id, 'name' => 'شهر ویرایش‌شده', 'province_id' => $province->id]]);
    }

    public function test_deletion_requires_a_different_senior_admin_and_keeps_audit_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $senior = User::factory()->create(['role' => 'admin', 'is_senior_admin' => true]);
        $province = Province::query()->create(['name' => 'استان خالی', 'tel_prefix' => '022']);
        $otherProvince = Province::query()->create(['name' => 'استان دیگر', 'tel_prefix' => '023']);
        $store = route('admin.locations.deletion-requests.store', $province);

        $this->actingAs($admin)->post($store, ['reason' => 'استان تکراری'])->assertSessionHasNoErrors();
        $this->post($store, ['reason' => 'درخواست دوباره'])->assertSessionHasErrors('province');
        $this->assertModelExists($province);
        $deletion = ProvinceDeletionRequest::query()->sole();
        $review = route('admin.locations.deletion-requests.review', $deletion);

        $this->patch($review, ['decision' => 'approve'])->assertForbidden();
        $this->get(route('admin.locations.deletion-requests.index'))->assertOk()->assertSee('استان تکراری');
        $this->actingAs($senior)
            ->post(route('admin.locations.deletion-requests.store', $otherProvince), ['reason' => 'درخواست خود مدیر ارشد'])
            ->assertSessionHasNoErrors();
        $own = ProvinceDeletionRequest::query()->where('province_id', $otherProvince->id)->sole();
        $this->patch(route('admin.locations.deletion-requests.review', $own), ['decision' => 'approve'])->assertForbidden();
        $this->get(route('admin.locations.deletion-requests.index'))->assertOk()->assertSee('تأیید حذف');
        $this->patch($review, ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertModelMissing($province);
        $this->assertDatabaseHas('province_deletion_requests', [
            'id' => $deletion->id, 'status' => 'approved', 'requested_by' => $admin->id, 'reviewed_by' => $senior->id,
        ]);
        $this->patch($review, ['decision' => 'approve'])->assertConflict();
    }

    public function test_review_rechecks_dependencies_and_can_reject_a_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $senior = User::factory()->create(['role' => 'admin', 'is_senior_admin' => true]);
        $province = Province::query()->create(['name' => 'استان دارای شهر', 'tel_prefix' => '021']);
        $store = route('admin.locations.deletion-requests.store', $province);

        $this->actingAs($admin)->post($store, ['reason' => 'بررسی'])->assertSessionHasNoErrors();
        $this->post(route('admin.locations.cities.store', $province), [
            'name' => 'شهر آزمایشی', 'county' => 'شهرستان آزمایشی',
        ])->assertSessionHasNoErrors();
        $deletion = ProvinceDeletionRequest::query()->sole();

        $this->actingAs($senior)->patch(route('admin.locations.deletion-requests.review', $deletion), [
            'decision' => 'approve',
        ])->assertSessionHasNoErrors();
        $this->assertModelExists($province);
        $this->assertSame('blocked', $deletion->fresh()->status);

        $this->actingAs($admin)->post($store, ['reason' => 'بررسی مجدد'])->assertSessionHasNoErrors();
        $deletion = ProvinceDeletionRequest::query()->latest('id')->firstOrFail();
        $this->actingAs($senior)->patch(route('admin.locations.deletion-requests.review', $deletion), [
            'decision' => 'reject',
        ])->assertSessionHasNoErrors();
        $this->assertSame('rejected', $deletion->fresh()->status);
    }

    public function test_system_admin_cannot_change_admin_roles_or_edit_senior_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $senior = User::factory()->create(['role' => 'admin', 'is_senior_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.users.edit', $senior))->assertForbidden();
        $this->patch(route('admin.users.toggle-active', $senior))->assertForbidden();
        $this->delete(route('admin.users.destroy', $senior))->assertForbidden();
        $this->patch(route('admin.users.toggle-role', $user))->assertForbidden();
        $this->actingAs($senior)->patch(route('admin.users.toggle-role', $user))->assertRedirect();
        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_console_can_promote_an_existing_active_account(): void
    {
        $user = User::factory()->create(['mobile' => '09121234567']);

        $this->artisan('admin:grant-senior', ['user' => $user->id])
            ->expectsConfirmation("Grant senior administrator permissions to account #{$user->id} ({$user->name})?", 'yes')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->isSeniorAdmin());
    }

    public function test_regular_user_cannot_manage_locations(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.locations.index'))
            ->assertForbidden();

        $this->post(route('admin.locations.provinces.store'), ['name' => 'استان آزمایشی', 'tel_prefix' => '021'])
            ->assertForbidden();
        $this->assertDatabaseMissing('provinces', ['name' => 'استان آزمایشی']);
    }
}
