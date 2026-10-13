<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Throwable;

class ImageService
{
    public function store(UploadedFile $file, string $directory, bool $withThumbnail = true): array
    {
        $name = (string) Str::uuid();
        $path = $directory.'/'.$name.'.webp';
        $thumbnailPath = $withThumbnail ? $directory.'/thumbs/'.$name.'.webp' : null;

        if ($this->canProcess() && (! $this->isHeic($file) || $this->canConvertHeic())) {
            try {
                $this->process($file, $path, $thumbnailPath ?? '');

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

        if ($this->isHeic($file)) {
            throw new \RuntimeException('تبدیل تصویر HEIC به WebP روی سرور در دسترس نیست.');
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
        $manager = new ImageManager($this->hasImagick() ? new ImagickDriver : new Driver);
        $quality = 100 - app(SystemOptions::class)->imageCompressionPercent();
        $sourcePath = $file->getRealPath();
        $convertedPath = null;

        if ($this->isHeic($file)) {
            $convertedPath = $this->convertHeicToPng($sourcePath);
            $sourcePath = $convertedPath;
        }

        try {
            $original = $manager->read($sourcePath)->scaleDown(width: 1200, height: 1200)->toWebp($quality);

            Storage::disk('public')->put($path, (string) $original);

            if ($thumbnailPath !== '') {
                $thumbnail = $manager->read($sourcePath)->cover(400, 400)->toWebp(max(30, $quality - 5));
                Storage::disk('public')->put($thumbnailPath, (string) $thumbnail);
            }
        } finally {
            if ($convertedPath && is_file($convertedPath)) {
                @unlink($convertedPath);
            }
        }
    }

    protected function canProcess(): bool
    {
        return $this->hasImagick() || (function_exists('imagecreatefromstring') && function_exists('imagewebp'));
    }

    protected function hasImagick(): bool
    {
        return extension_loaded('imagick') && class_exists('Imagick');
    }

    protected function isHeic(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), ['heic', 'heif'], true)
            || in_array(strtolower((string) $file->getMimeType()), ['image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence'], true);
    }

    protected function canConvertHeic(): bool
    {
        return is_file(base_path('scripts/convert-heic.mjs')) && $this->nodeBinaryWorks();
    }

    protected function convertHeicToPng(string $sourcePath): string
    {
        $targetPath = tempnam(sys_get_temp_dir(), 'digistock-heic-').'.png';
        @unlink(substr($targetPath, 0, -4));
        $pipes = [];
        $process = proc_open(
            ['node', base_path('scripts/convert-heic.mjs'), $sourcePath, $targetPath],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        if (! is_resource($process)) {
            throw new \RuntimeException('امکان اجرای مبدل HEIC روی سرور وجود ندارد.');
        }

        $error = trim(stream_get_contents($pipes[2]));
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0 || ! is_file($targetPath)) {
            @unlink($targetPath);
            throw new \RuntimeException('تبدیل تصویر HEIC به WebP انجام نشد.'.($error !== '' ? ' '.$error : ''));
        }

        return $targetPath;
    }

    protected function nodeBinaryWorks(): bool
    {
        $process = @proc_open(['node', '--version'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            return false;
        }

        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process) === 0;
    }
}
