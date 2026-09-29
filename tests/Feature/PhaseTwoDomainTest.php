<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\PhoneModel;
use App\Models\User;
use App\Services\ListingRules;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_catalog_seeder_creates_brands_models_and_dynamic_attributes(): void
    {
        $this->assertGreaterThanOrEqual(14, Brand::count());
        $this->assertGreaterThanOrEqual(100, PhoneModel::count());
        $this->assertSame(11, Attribute::count());
        $this->assertTrue(Attribute::where('is_filterable', true)->exists());
        $this->assertTrue(PhoneModel::first()->modelAttributes()->exists());
    }

    public function test_listing_relations_support_eav_values(): void
    {
        [$user, $brand, $model] = $this->listingContext();
        $listing = $this->createListing($user, $brand, $model);
        $attribute = Attribute::where('name', 'حافظه داخلی')->firstOrFail();

        $value = ListingAttributeValue::create([
            'listing_id' => $listing->id,
            'attribute_id' => $attribute->id,
            'value_integer' => 256,
        ]);

        $this->assertSame($brand->id, $listing->brand->id);
        $this->assertSame($model->id, $listing->phoneModel->id);
        $this->assertSame(256, $listing->fresh()->attributeValues->first()->value_integer);
        $this->assertSame($attribute->id, $value->attribute->id);
    }

    public function test_listing_policy_blocks_other_users_and_allows_admin(): void
    {
        [$owner, $brand, $model] = $this->listingContext();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $listing = $this->createListing($owner, $brand, $model);

        $this->assertTrue($owner->can('update', $listing));
        $this->assertFalse($otherUser->can('update', $listing));
        $this->assertTrue($admin->can('update', $listing));
    }

    public function test_admin_route_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();

        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('پنل مدیریت');
    }

    public function test_listing_rules_enforce_expiry_and_state_transitions(): void
    {
        [$user, $brand, $model] = $this->listingContext();
        $rules = app(ListingRules::class);
        $listing = $this->createListing($user, $brand, $model);

        $published = $rules->publish($listing);
        $this->assertSame(ListingStatus::Approved, $published->status);
        $this->assertNotNull($published->published_at);
        $this->assertTrue($published->expires_at->isBetween(now()->addDays(29), now()->addDays(31)));

        $rejected = $rules->reject($published, 'تصویر آگهی واضح نیست');
        $this->assertSame(ListingStatus::Rejected, $rejected->status);
        $this->assertSame('تصویر آگهی واضح نیست', $rejected->rejection_reason);
    }

    private function listingContext(): array
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        return [$user, $brand, $model];
    }

    private function createListing(User $user, Brand $brand, PhoneModel $model): Listing
    {
        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => $model->name.' سالم',
            'slug' => Str::uuid()->toString(),
            'price' => 25000000,
            'status' => ListingStatus::Pending,
        ]);
    }
}
