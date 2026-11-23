<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelAttribute extends Model
{
    use HasFactory;

    protected $fillable = ['phone_model_id', 'attribute_id', 'is_required', 'sort_order', 'custom_options'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'custom_options' => 'array'];
    }

    public function phoneModel(): BelongsTo
    {
        return $this->belongsTo(PhoneModel::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
