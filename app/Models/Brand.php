<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_en', 'slug', 'logo', 'featured_image', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function phoneModels(): HasMany
    {
        return $this->hasMany(PhoneModel::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function featuredImageUrl(): string
    {
        return $this->featured_image
            ? Storage::disk('public')->url($this->featured_image)
            : asset(match (strtolower((string) $this->name_en)) {
                'apple' => 'images/brands/apple.webp',
                'samsung' => 'images/brands/samsung.webp',
                'xiaomi' => 'images/brands/xiaomi.webp',
                default => 'images/brand-default.png',
            });
    }
}
