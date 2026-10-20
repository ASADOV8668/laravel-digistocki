<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeOptionRestriction extends Model
{
    use HasFactory;

    protected $fillable = ['attribute_id', 'phone_model_ids', 'options'];

    protected function casts(): array
    {
        return [
            'phone_model_ids' => 'array',
            'options' => 'array',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
