<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = ['listing_id', 'attribute_id', 'value_string', 'value_integer', 'value_decimal', 'value_boolean', 'value_json'];

    protected function casts(): array
    {
        return ['value_decimal' => 'decimal:2', 'value_boolean' => 'boolean', 'value_json' => 'array'];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
