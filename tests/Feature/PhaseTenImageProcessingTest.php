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

class PhaseTenImageProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
    }

    public function test_listing_upload_is_stored_through_image_service_and_image_metadata(): void
    {
        $user = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی با تصویر پردازش‌شده',
            'price' => 25000000,
            'images' => [UploadedFile::fake()->create('phone.jpg', 100, 'image/jpeg')],
        ])->assertRedirect();

        $image = Listing::where('title', 'آگهی با تصویر پردازش‌شده')->firstOrFail()->images()->firstOrFail();
        $this->assertNotSame('', $image->path);
        $this->assertTrue($image->thumbnail_path === null || Str::endsWith($image->thumbnail_path, '.webp'));
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_deleting_an_image_removes_original_and_thumbnail_files(): void
    {
        $user = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی حذف تصویر',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Pending,
        ]);
        Storage::disk('public')->put('listings/test/original.webp', 'original');
        Storage::disk('public')->put('listings/test/thumb.webp', 'thumbnail');
        $image = ListingImage::create(['listing_id' => $listing->id, 'path' => 'listings/test/original.webp', 'thumbnail_path' => 'listings/test/thumb.webp', 'is_primary' => true, 'sort_order' => 0]);

        $this->actingAs($user)->delete(route('listings.images.destroy', [$listing, $image]))->assertRedirect();

        Storage::disk('public')->assertMissing('listings/test/original.webp');
        Storage::disk('public')->assertMissing('listings/test/thumb.webp');
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }

    public function test_another_user_cannot_delete_the_listing_image(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی تصویر خصوصی',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Pending,
        ]);
        Storage::disk('public')->put('listings/private/original.webp', 'original');
        $image = ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/private/original.webp',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($otherUser)
            ->delete(route('listings.images.destroy', [$listing, $image]))
            ->assertForbidden();

        $this->assertDatabaseHas('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists('listings/private/original.webp');
    }
}
