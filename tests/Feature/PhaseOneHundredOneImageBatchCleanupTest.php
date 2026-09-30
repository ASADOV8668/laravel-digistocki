<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PhaseOneHundredOneImageBatchCleanupTest extends TestCase
{
    public function test_image_artifacts_are_deleted_in_one_filesystem_operation(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')
            ->once()
            ->with([
                'listings/42/first.webp',
                'listings/42/thumbs/first.webp',
                'listings/42/second.jpg',
            ])
            ->andReturnTrue();

        Storage::shouldReceive('disk')->once()->with('public')->andReturn($disk);

        app(ImageService::class)->deleteMany([
            ['listings/42/first.webp', 'listings/42/thumbs/first.webp'],
            ['listings/42/second.jpg', null],
        ]);

        $this->addToAssertionCount(1);
    }
}
