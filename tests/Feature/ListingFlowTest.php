<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class ListingFlowTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_public_index_only_shows_approved_listings_and_show_counts_views(): void
    {
        [$user, $brand, $model] = $this->context();
        $approved = $this->listing($user, $brand, $model, 'آگهی تأییدشده', ListingStatus::Approved);
        $pending = $this->listing($user, $brand, $model, 'آگهی در انتظار', ListingStatus::Pending);

        $this->get(route('listings.index'))->assertOk()->assertSee($approved->title)->assertDontSee($pending->title);
        $this->get(route('listings.show', $approved))->assertOk()->assertSee($approved->title);
        $this->assertSame(1, $approved->refresh()->views_count);
    }

    public function test_authenticated_user_can_create_pending_listing_with_eav_value(): void
    {
        [$user, $brand, $model] = $this->context();
        $memory = Attribute::where('name', 'حافظه داخلی')->firstOrFail();

        $response = $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تست آیفون',
            'description' => 'توضیحات تست',
            'price' => 32000000,
            'attributes' => array_replace($this->requiredAttributeValues($model), [$memory->id => 256]),
        ]);

        $response->assertRedirect();
        $listing = Listing::query()->where('title', 'آگهی تست آیفون')->firstOrFail();
        $this->assertSame(ListingStatus::Pending, $listing->status);
        $this->assertSame(256, $listing->attributeValues()->firstOrFail()->value_integer);
    }

    public function test_listing_store_validates_required_fields_and_model_brand_consistency(): void
    {
        [$user, $brand] = $this->context();
        $otherBrand = Brand::where('id', '<>', $brand->id)->firstOrFail();
        $otherModel = $otherBrand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [])->assertSessionHasErrors(['brand_id', 'phone_model_id', 'title', 'price']);
        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $otherModel->id,
            'title' => 'مدل نامعتبر',
            'price' => 100,
        ])->assertNotFound();
    }

    public function test_admin_can_approve_and_reject_listing(): void
    {
        [$owner, $brand, $model] = $this->context();
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $listing = $this->listing($owner, $brand, $model, 'برای بررسی', ListingStatus::Pending);

        $this->actingAs($admin)->patch(route('admin.listings.approve', $listing))->assertRedirect();
        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
        $this->actingAs($admin)->patch(route('admin.listings.reject', $listing), ['rejection_reason' => 'دلیل کافی برای رد'])->assertRedirect();
        $this->assertSame(ListingStatus::Rejected, $listing->refresh()->status);
    }

    private function context(): array
    {
        $brand = Brand::firstOrFail();

        return [User::factory()->create(), $brand, $brand->phoneModels()->firstOrFail()];
    }

    private function listing(User $user, Brand $brand, PhoneModel $model, string $title, ListingStatus $status): Listing
    {
        return Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => $title, 'slug' => Str::uuid(), 'price' => 1000000, 'status' => $status]);
    }
}
