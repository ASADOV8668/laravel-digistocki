<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'unit', 'options', 'is_filterable', 'is_required', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'options' => 'array',
            'is_filterable' => 'boolean',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function modelAttributes(): HasMany
    {
        return $this->hasMany(ModelAttribute::class);
    }

    public function phoneModels(): BelongsToMany
    {
        return $this->belongsToMany(PhoneModel::class, 'model_attributes')->withPivot(['is_required', 'sort_order'])->withTimestamps();
    }

    public function listingValues(): HasMany
    {
        return $this->hasMany(ListingAttributeValue::class);
    }
}
