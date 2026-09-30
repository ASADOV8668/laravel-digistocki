<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhoneModel extends Model
{
    use HasFactory;

    protected $table = 'phone_models';

    protected $fillable = ['brand_id', 'name', 'name_fa', 'name_en', 'slug', 'release_year', 'is_active'];

    protected function casts(): array
    {
        return ['release_year' => 'integer', 'is_active' => 'boolean'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function modelAttributes(): HasMany
    {
        return $this->hasMany(ModelAttribute::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'model_attributes')->withPivot(['is_required', 'sort_order'])->withTimestamps();
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }
}
