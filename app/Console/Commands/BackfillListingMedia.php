<?php

namespace App\Console\Commands;

use App\Models\ListingImage;
use App\Services\ListingMediaService;
use Illuminate\Console\Command;
use Throwable;

class BackfillListingMedia extends Command
{
    protected $signature = 'media:backfill-listing-images {--limit=0 : Maximum number of legacy images to process}';

    protected $description = 'Register existing listing images in the reusable media library without deleting legacy files';

    public function handle(ListingMediaService $mediaService): int
    {
        $query = ListingImage::query()
            ->whereNull('media_id')
            ->with(['listing.user'])
            ->orderBy('id');

        if ((int) $this->option('limit') > 0) {
            $query->limit((int) $this->option('limit'));
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($query->get() as $image) {
            $owner = $image->listing?->user;
            if (! $owner || blank($image->path) || ! file_exists(storage_path('app/public/'.$image->path))) {
                $skipped++;
                continue;
            }

            try {
                $media = $owner
                    ->addMediaFromDisk($image->path, 'public')
                    ->usingName(pathinfo($image->path, PATHINFO_FILENAME))
                    ->toMediaCollection('listing-images', 'public');

                $image->forceFill($mediaService->imageData($media))->save();
                $processed++;
            } catch (Throwable $exception) {
                $failed++;
                $this->warn("تصویر {$image->id} منتقل نشد: {$exception->getMessage()}");
            }
        }

        $this->info("رسانه‌ها ثبت شدند: {$processed} | ردشده: {$skipped} | خطادار: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
