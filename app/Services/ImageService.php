<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class ImageService
{
    public function store(UploadedFile $file, string $directory): array
    {
        $name = (string) Str::uuid();
        $path = $directory.'/'.$name.'.webp';
        $thumbnailPath = $directory.'/thumbs/'.$name.'.webp';

        if ($this->canProcess()) {
            try {
                $this->process($file, $path, $thumbnailPath);

                return ['path' => $path, 'thumbnail_path' => $thumbnailPath];
            } catch (Throwable $exception) {
                try {
                    $this->deleteMany([[$path, $thumbnailPath]]);
                } catch (Throwable $cleanupException) {
                    Log::warning('Listing image cleanup failed after processing error.', [
                        'path' => $path,
                        'thumbnail_path' => $thumbnailPath,
                        'error' => $cleanupException->getMessage(),
                    ]);
                }

                Log::warning('Listing image processing failed; storing original file.', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $fallbackPath = $file->storeAs($directory, $name.'.'.$file->extension(), 'public');

        return ['path' => $fallbackPath, 'thumbnail_path' => null];
    }

    public function delete(?string $path, ?string $thumbnailPath = null): void
    {
        $this->deleteMany([[$path, $thumbnailPath]]);
    }

    public function deleteMany(iterable $files): void
    {
        $paths = [];

        foreach ($files as $file) {
            $paths = [...$paths, ...(is_array($file) ? $file : [$file])];
        }

        $paths = array_values(array_unique(array_filter(
            $paths,
            fn ($path): bool => is_string($path) && $path !== '',
        )));

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }

    protected function process(UploadedFile $file, string $path, string $thumbnailPath): void
    {
        $manager = new ImageManager(new Driver());
        $original = $manager->read($file->getRealPath())->scaleDown(width: 1200, height: 1200)->toWebp(82);
        $thumbnail = $manager->read($file->getRealPath())->cover(400, 400)->toWebp(78);

        Storage::disk('public')->put($path, (string) $original);
        Storage::disk('public')->put($thumbnailPath, (string) $thumbnail);
    }

    protected function canProcess(): bool
    {
        return function_exists('imagecreatefromstring') && function_exists('imagewebp');
    }
}
