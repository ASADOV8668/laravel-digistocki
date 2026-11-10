<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\PhoneModel;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ListingMediaService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingMediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_images_can_reference_reusable_media_and_are_displayable(): void
    {
        Storage::fake('public');
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $brand = Brand::query()->firstOrFail();
        $model = PhoneModel::query()->where('brand_id', $brand->id)->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'رسانه آزمایشی',
            'slug' => 'media-test-'.uniqid(),
            'price' => 1000000,
            'status' => 'approved',
        ]);

        $mediaService = app(ListingMediaService::class);
        $media = $mediaService->createFromUpload(
            $user,
            UploadedFile::fake()->create('phone.webp', 100, 'image/webp'),
            app(ImageService::class),
        );
        $image = $mediaService->attachToListing($listing, $media, true, 0);

        $this->assertSame((int) $media->getKey(), (int) $image->media_id);
        $this->assertSame($media->getUrl(), $image->displayUrl());
        $this->assertSame(
            $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
            $image->displayUrl(true),
        );

        $this->actingAs($user)
            ->get(route('listings.media.index'))
            ->assertOk()
            ->assertJsonCount(1, 'media')
            ->assertJsonPath('media.0.id', $media->getKey());
    }

    public function test_legacy_listing_images_keep_their_display_fallback(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();
        $brand = Brand::query()->firstOrFail();
        $model = PhoneModel::query()->where('brand_id', $brand->id)->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'رسانه قدیمی',
            'slug' => 'legacy-media-test-'.uniqid(),
            'price' => 1000000,
            'status' => 'approved',
        ]);
        $image = ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/legacy.webp',
            'thumbnail_path' => 'listings/legacy-thumb.webp',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->assertStringEndsWith('/storage/listings/legacy.webp', $image->displayUrl());
        $this->assertStringEndsWith('/storage/listings/legacy-thumb.webp', $image->displayUrl(true));
    }
}
