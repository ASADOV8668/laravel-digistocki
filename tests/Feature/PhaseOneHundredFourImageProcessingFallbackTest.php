<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PhaseOneHundredFourImageProcessingFallbackTest extends TestCase
{
    public function test_partial_processed_files_are_removed_before_original_fallback_is_stored(): void
    {
        Storage::fake('public');

        $service = new class extends ImageService
        {
            public ?string $partialPath = null;

            protected function canProcess(): bool
            {
                return true;
            }

            protected function process(UploadedFile $file, string $path, string $thumbnailPath): void
            {
                $this->partialPath = $path;
                Storage::disk('public')->put($path, 'partial-webp');

                throw new RuntimeException('forced partial processing failure');
            }
        };

        $result = $service->store(
            UploadedFile::fake()->create('phone.jpg', 100, 'image/jpeg'),
            'listings/rollback',
        );

        $this->assertNotNull($service->partialPath);
        Storage::disk('public')->assertMissing($service->partialPath);
        Storage::disk('public')->assertExists($result['path']);
        $this->assertNull($result['thumbnail_path']);
    }
}
