<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhoneSeries extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = ['brand_id', 'name', 'name_fa', 'name_en', 'slug', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function phoneModels(): HasMany
    {
        return $this->hasMany(PhoneModel::class);
    }
}
