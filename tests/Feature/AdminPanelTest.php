<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $this->admin->save();
    }

    public function test_admin_can_filter_and_moderate_listings(): void
    {
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $owner->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->firstOrFail()->id, 'title' => 'آگهی مدیریت', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Pending]);

        $this->actingAs($this->admin)->get(route('admin.listings.index', ['status' => 'pending']))->assertOk()->assertSee($listing->title);
        $this->actingAs($this->admin)->patch(route('admin.listings.approve', $listing))->assertRedirect();
        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
    }

    public function test_admin_can_manage_users_without_being_able_to_disable_self(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk()->assertSee($user->name);
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-active', $user))->assertRedirect();
        $this->assertFalse($user->refresh()->is_active);
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-role', $user))->assertRedirect();
        $this->assertTrue($user->refresh()->isAdmin());
        $this->actingAs($this->admin)->patch(route('admin.users.toggle-active', $this->admin))->assertStatus(422);
    }

    public function test_admin_can_manage_catalog_and_dynamic_attributes(): void
    {
        $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'برند تست', 'name_en' => 'Test Brand'])->assertRedirect();
        $brand = Brand::where('name_en', 'Test Brand')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.phone-models.store'), ['brand_id' => $brand->id, 'name' => 'Test Phone', 'release_year' => 2026])->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.phone-models.store'), ['brand_id' => $brand->id, 'name' => 'Test Phone', 'release_year' => 2026])->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.attributes.store'), ['name' => 'ویژگی تست', 'type' => 'string', 'unit' => 'واحد', 'is_filterable' => 1])->assertRedirect();
        $this->assertDatabaseHas('phone_models', ['brand_id' => $brand->id, 'name' => 'Test Phone']);
        $this->assertSame(2, $brand->phoneModels()->where('name', 'Test Phone')->count());
        $this->assertNotSame($brand->phoneModels()->where('name', 'Test Phone')->first()->slug, $brand->phoneModels()->where('name', 'Test Phone')->latest('id')->first()->slug);
        $this->assertDatabaseHas('attributes', ['name' => 'ویژگی تست', 'is_filterable' => 1]);
        $attribute = \App\Models\Attribute::where('name', 'ویژگی تست')->firstOrFail();
        $this->actingAs($this->admin)->patch(route('admin.attributes.update', $attribute), ['name' => 'ویژگی ویرایش‌شده', 'type' => 'string', 'unit' => 'واحد جدید', 'is_filterable' => 1])->assertRedirect();
        $this->assertDatabaseHas('attributes', ['id' => $attribute->id, 'name' => 'ویژگی ویرایش‌شده', 'unit' => 'واحد جدید']);
        $model = $brand->phoneModels()->where('name', 'Test Phone')->firstOrFail();
        $this->actingAs($this->admin)->patch(route('admin.phone-models.update', $model), ['brand_id' => $brand->id, 'name' => 'Updated Phone', 'name_fa' => 'مدل به‌روزشده', 'name_en' => 'Updated Phone', 'release_year' => 2027, 'attribute_ids' => [$attribute->id]])->assertRedirect();
        $this->assertDatabaseHas('phone_models', ['id' => $model->id, 'name' => 'Updated Phone', 'release_year' => 2027]);
    }

    public function test_admin_can_search_catalog_entities(): void
    {
        $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'برند جستجو', 'name_en' => 'Search Brand']);
        $brand = Brand::where('name_en', 'Search Brand')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.phone-models.store'), ['brand_id' => $brand->id, 'name' => 'Search Phone', 'name_fa' => 'مدل جستجو', 'name_en' => 'Search Phone']);
        $this->actingAs($this->admin)->post(route('admin.attributes.store'), ['name' => 'ویژگی جستجو', 'type' => 'string']);

        $this->actingAs($this->admin)->get(route('admin.brands.index', ['q' => 'جستجو']))->assertOk()->assertSee('برند جستجو');
        $this->actingAs($this->admin)->get(route('admin.phone-models.index', ['q' => 'Search Phone']))->assertOk()->assertSee('Search Phone');
        $this->actingAs($this->admin)->get(route('admin.attributes.index', ['q' => 'ویژگی جستجو']))->assertOk()->assertSee('ویژگی جستجو');
    }

    public function test_admin_can_review_reports_and_regular_user_is_blocked(): void
    {
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $owner->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->firstOrFail()->id, 'title' => 'آگهی گزارش‌شده', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);
        $report = Report::create(['listing_id' => $listing->id, 'user_id' => $owner->id, 'reason' => 'اطلاعات نادرست']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk()->assertSee($report->reason);
        $this->actingAs($this->admin)->patch(route('admin.reports.status', $report), ['status' => 'resolved'])->assertRedirect();
        $this->assertSame('resolved', $report->refresh()->status);
    }
}
