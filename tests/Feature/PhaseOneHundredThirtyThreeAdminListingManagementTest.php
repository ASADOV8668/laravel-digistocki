<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseOneHundredThirtyThreeAdminListingManagementTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_can_create_and_edit_listing_for_another_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['name' => 'صاحب آگهی', 'mobile' => '09121234567']);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $attributes = $this->requiredAttributeValues($model);

        $this->actingAs($admin)->get(route('admin.listings.create'))->assertOk()->assertSee('افزودن آگهی');
        $response = $this->actingAs($admin)->post(route('admin.listings.store'), [
            'user_id' => $owner->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id,
            'title' => 'آگهی ساخته‌شده توسط مدیریت', 'description' => 'توضیحات مدیریت', 'price' => 1200000,
            'attributes' => $attributes,
        ]);
        $response->assertRedirect(route('admin.listings.index'));
        $listing = Listing::query()->where('title', 'آگهی ساخته‌شده توسط مدیریت')->firstOrFail();
        $this->assertSame($owner->id, $listing->user_id);

        $this->actingAs($admin)->get(route('admin.listings.edit', $listing))->assertOk()->assertSee($owner->name);
        $this->actingAs($admin)->put(route('admin.listings.update', $listing), [
            'user_id' => $owner->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id,
            'title' => 'آگهی ویرایش‌شده توسط مدیریت', 'price' => 1500000, 'attributes' => $attributes,
        ])->assertRedirect(route('admin.listings.index'));
        $this->assertSame('آگهی ویرایش‌شده توسط مدیریت', $listing->refresh()->title);
    }

    public function test_admin_user_search_returns_name_and_mobile_matches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'جستجوی اختصاصی', 'mobile' => '09129876543']);

        $this->actingAs($admin)->get(route('admin.users.search', ['q' => '987654']))
            ->assertOk()->assertJsonFragment(['mobile' => '09129876543']);
    }

    public function test_regular_user_cannot_manage_admin_listings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.listings.create'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.search', ['q' => '09']))->assertForbidden();
    }
}
