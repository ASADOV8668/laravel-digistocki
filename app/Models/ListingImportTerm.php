<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingImportTerm extends Model
{
    protected $fillable = ['emoji', 'meaning', 'attribute_id', 'attribute_value'];

    protected static function booted(): void
    {
        static::saving(function (self $term): void {
            $term->source_hash = self::sourceHash($term->emoji);
        });
    }

    public static function sourceHash(string $source): string
    {
        return hash('sha256', mb_strtolower($source));
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
