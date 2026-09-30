<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseOneHundredThreeImageTransactionCleanupTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    public function test_failed_listing_transaction_removes_files_created_before_rollback(): void
    {
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
        ListingImage::creating(fn () => throw new RuntimeException('forced image insert failure'));

        $user = User::factory()->create();
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('listings.store'), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی تراکنش ناموفق',
                'price' => 1000000,
                'attributes' => $this->requiredAttributeValues($model),
                'images' => [UploadedFile::fake()->create('rollback.jpg', 100, 'image/jpeg')],
            ]);
            $this->fail('The image insert failure should have been rethrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced image insert failure', $exception->getMessage());
        }

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('listings', 0);
        $this->assertDatabaseCount('listing_images', 0);
    }
}
