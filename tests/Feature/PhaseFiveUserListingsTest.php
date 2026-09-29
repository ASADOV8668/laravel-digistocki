<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseFiveUserListingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
    }

    public function test_user_dashboard_only_lists_the_authenticated_users_ads(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$brand, $model] = $this->catalogContext();
        $mine = $this->listing($owner, $brand, $model, 'آگهی من', ListingStatus::Pending);
        $otherListing = $this->listing($other, $brand, $model, 'آگهی کاربر دیگر', ListingStatus::Approved);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSee($mine->title)->assertDontSee($otherListing->title);
    }

    public function test_user_can_create_listing_with_primary_image(): void
    {
        $user = User::factory()->create();
        [$brand, $model] = $this->catalogContext();
        $image = UploadedFile::fake()->create('phone.jpg', 100, 'image/jpeg');

        $this->actingAs($user)->post(route('listings.store'), ['brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'آگهی تصویری', 'price' => 25000000, 'images' => [$image]])->assertRedirect();
        $listing = Listing::where('title', 'آگهی تصویری')->firstOrFail();
        $stored = $listing->images()->firstOrFail();
        $this->assertTrue($stored->is_primary);
        Storage::disk('public')->assertExists($stored->path);
    }

    public function test_editing_rejected_listing_returns_it_to_pending(): void
    {
        $user = User::factory()->create();
        [$brand, $model] = $this->catalogContext();
        $listing = $this->listing($user, $brand, $model, 'آگهی ردشده', ListingStatus::Rejected);

        $this->actingAs($user)->put(route('listings.update', $listing), ['brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'آگهی اصلاح‌شده', 'price' => 30000000])->assertRedirect();
        $this->assertSame(ListingStatus::Pending, $listing->refresh()->status);
        $this->assertSame('آگهی اصلاح‌شده', $listing->title);
    }

    public function test_owner_can_mark_approved_listing_sold_and_other_user_cannot_edit_it(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$brand, $model] = $this->catalogContext();
        $listing = $this->listing($owner, $brand, $model, 'آگهی آماده فروش', ListingStatus::Approved);

        $this->actingAs($other)->get(route('listings.edit', $listing))->assertForbidden();
        $this->actingAs($owner)->patch(route('listings.sold', $listing))->assertRedirect()->assertSessionHas('status', 'آگهی به‌عنوان فروخته‌شده علامت خورد.');
        $this->assertSame(ListingStatus::Sold, $listing->refresh()->status);
        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSee('آگهی به‌عنوان فروخته‌شده علامت خورد.');
    }

    private function catalogContext(): array
    {
        $brand = Brand::firstOrFail();
        return [$brand, $brand->phoneModels()->firstOrFail()];
    }

    private function listing(User $user, Brand $brand, $model, string $title, ListingStatus $status): Listing
    {
        return Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => $title, 'slug' => Str::uuid(), 'price' => 1000000, 'status' => $status]);
    }
}
