<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredTwoAccountImageCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_account_removes_all_listing_image_artifacts(): void
    {
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');

        $user = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی قبل از حذف حساب',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Pending,
        ]);

        $files = [
            ['listings/'.$listing->id.'/one.webp', 'listings/'.$listing->id.'/thumbs/one.webp'],
            ['listings/'.$listing->id.'/two.jpg', null],
        ];
        foreach ($files as [$path, $thumbnailPath]) {
            Storage::disk('public')->put($path, 'image');
            if ($thumbnailPath) {
                Storage::disk('public')->put($thumbnailPath, 'thumbnail');
            }
            ListingImage::create([
                'listing_id' => $listing->id,
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'is_primary' => $path === $files[0][0],
                'sort_order' => 0,
            ]);
        }

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        foreach ($files as [$path, $thumbnailPath]) {
            Storage::disk('public')->assertMissing($path);
            if ($thumbnailPath) {
                Storage::disk('public')->assertMissing($thumbnailPath);
            }
        }

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('listing_images', ['listing_id' => $listing->id]);
    }
}
