<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseNinetyFourEditImagePickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_edit_page_shows_preview_picker_with_remaining_image_capacity(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی برای ویرایش تصویر',
            'slug' => Str::uuid(),
            'price' => 15000000,
            'status' => ListingStatus::Pending,
        ]);

        ListingImage::insert(collect(range(1, 3))->map(fn (int $sortOrder) => [
            'listing_id' => $listing->id,
            'path' => "listings/{$listing->id}/image-{$sortOrder}.webp",
            'thumbnail_path' => "listings/{$listing->id}/image-{$sortOrder}-thumb.webp",
            'is_primary' => $sortOrder === 1,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        $this->actingAs($user)
            ->get(route('listings.edit', $listing))
            ->assertOk()
            ->assertSee('imagePicker(5, 5)', false)
            ->assertSee('ظرفیت باقی‌مانده: 5 تصویر')
            ->assertSee('preview.url', false);
    }
}
