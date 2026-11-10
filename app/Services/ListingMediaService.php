<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ListingMediaService
{
    public function createFromUpload(User $owner, UploadedFile $file, ImageService $imageService): Media
    {
        $staged = $imageService->store($file, 'media-staging', false);

        try {
            return $owner
                ->addMediaFromDisk($staged['path'], 'public')
                ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('listing-images', 'public');
        } finally {
            $imageService->delete($staged['path'] ?? null);
        }
    }

    public function ownedMedia(User $owner, ?int $limit = 50)
    {
        $query = $owner->media()
            ->where('collection_name', 'listing-images')
            ->latest('created_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (Media $media): array => $this->payload($media))
            ->values();
    }

    public function findOwned(User $owner, int $mediaId): ?Media
    {
        return $owner->media()
            ->whereKey($mediaId)
            ->where('collection_name', 'listing-images')
            ->first();
    }

    public function imageData(Media $media): array
    {
        $path = $media->getPathRelativeToRoot();
        $thumbnail = $media->hasGeneratedConversion('thumb')
            ? $media->getPathRelativeToRoot('thumb')
            : null;

        return [
            'media_id' => $media->getKey(),
            'path' => $path,
            'thumbnail_path' => $thumbnail,
        ];
    }

    public function attachToListing(Listing $listing, Media $media, bool $primary, int $sortOrder): ListingImage
    {
        $paths = $this->imageData($media);

        return $listing->images()->create([
            ...$paths,
            'is_primary' => $primary,
            'sort_order' => $sortOrder,
        ]);
    }

    public function deleteIfUnused(?Media $media): void
    {
        if (! $media || ListingImage::query()->where('media_id', $media->getKey())->exists()) {
            return;
        }

        $media->delete();
    }

    /** @return array<string, mixed> */
    private function payload(Media $media): array
    {
        $paths = $this->imageData($media);

        return [
            'id' => $media->getKey(),
            'name' => $media->name,
            'file_name' => $media->file_name,
            'url' => $media->getUrl(),
            'thumbnail_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
            ...$paths,
        ];
    }
}
