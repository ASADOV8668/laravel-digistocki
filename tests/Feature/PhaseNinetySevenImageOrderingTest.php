<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseNinetySevenImageOrderingTest extends TestCase
{
    use RefreshDatabase;
    use BuildsListingAttributePayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
    }

    public function test_uploaded_images_keep_order_and_only_first_image_is_primary(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی ترتیب تصاویر',
            'price' => 21000000,
            'attributes' => $this->requiredAttributeValues($model),
            'images' => [
                UploadedFile::fake()->create('first.jpg', 20, 'image/jpeg'),
                UploadedFile::fake()->create('second.jpg', 20, 'image/jpeg'),
            ],
        ])->assertRedirect();

        $listing = Listing::where('title', 'آگهی ترتیب تصاویر')->firstOrFail();
        $images = $listing->images()->get();

        $this->assertCount(2, $images);
        $this->assertTrue((bool) $images[0]->is_primary);
        $this->assertFalse((bool) $images[1]->is_primary);
        $this->assertSame([1, 2], $images->pluck('sort_order')->all());
    }
}
