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

    public function test_admin_can_browse_all_listing_media_with_uploader_details(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create([
            'name' => 'کاربر رسانه',
            'mobile' => '09120000000',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $mediaService = app(ListingMediaService::class);

        $mediaService->createFromUpload(
            $owner,
            UploadedFile::fake()->create('phone-one.webp', 100, 'image/webp'),
            app(ImageService::class),
        );
        $mediaService->createFromUpload(
            $owner,
            UploadedFile::fake()->create('phone-two.webp', 100, 'image/webp'),
            app(ImageService::class),
        );

        $this->actingAs($admin)
            ->get(route('admin.listings.media.index', ['user_id' => $owner->id]))
            ->assertOk()
            ->assertJsonCount(2, 'media');

        $this->actingAs($admin)
            ->get(route('admin.media.index', ['q' => '09120000000']))
            ->assertOk()
            ->assertSee('کاربر رسانه')
            ->assertSee('phone-one')
            ->assertSee('phone-two');
    }

    public function test_admin_can_delete_unused_media_with_confirmation_form_and_used_media_is_protected(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $mediaService = app(ListingMediaService::class);
        $imageService = app(ImageService::class);

        $unused = $mediaService->createFromUpload(
            $owner,
            UploadedFile::fake()->create('unused.webp', 100, 'image/webp'),
            $imageService,
        );

        $this->actingAs($admin)
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('آیا از حذف این رسانه مطمئن هستید؟', false)
            ->assertSee(route('admin.media.destroy', $unused), false);

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $unused))
            ->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $unused->getKey()]);

        $brand = Brand::query()->first() ?? Brand::create(['name' => 'برند آزمایشی', 'slug' => 'test-brand']);
        $model = PhoneModel::query()->where('brand_id', $brand->id)->first()
            ?? PhoneModel::create(['brand_id' => $brand->id, 'name' => 'مدل آزمایشی', 'slug' => 'test-model']);
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'رسانه در حال استفاده',
            'slug' => 'used-media-test-'.uniqid(),
            'price' => 1000000,
            'status' => 'approved',
        ]);
        $used = $mediaService->createFromUpload(
            $owner,
            UploadedFile::fake()->create('used.webp', 100, 'image/webp'),
            $imageService,
        );
        $mediaService->attachToListing($listing, $used, true, 0);

        $this->actingAs($admin)
            ->delete(route('admin.media.destroy', $used))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('media', ['id' => $used->getKey()]);
    }
}
