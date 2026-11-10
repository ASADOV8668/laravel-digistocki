<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ListingImage extends Model
{
    use HasFactory;

    protected $fillable = ['listing_id', 'media_id', 'path', 'thumbnail_path', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function displayPath(bool $thumbnail = false): ?string
    {
        if ($this->media) {
            $conversion = $thumbnail && $this->media->hasGeneratedConversion('thumb') ? 'thumb' : '';

            return $this->media->getPathRelativeToRoot($conversion);
        }

        $path = $thumbnail ? ($this->thumbnail_path ?: $this->path) : $this->path;

        return filled($path) ? $path : null;
    }

    public function displayUrl(bool $thumbnail = false): ?string
    {
        if ($this->media) {
            $conversion = $thumbnail && $this->media->hasGeneratedConversion('thumb') ? 'thumb' : '';

            return $this->media->getUrl($conversion);
        }

        $path = $this->displayPath($thumbnail);

        return filled($path) ? asset('storage/'.ltrim($path, '/')) : null;
    }
}
