<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_en', 'slug', 'logo', 'featured_image', 'is_active', 'can_post_listings'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'can_post_listings' => 'boolean'];
    }

    public function phoneModels(): HasMany
    {
        return $this->hasMany(PhoneModel::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function phoneSeries(): HasMany
    {
        return $this->hasMany(PhoneSeries::class)->orderBy('sort_order')->orderBy('name');
    }

    public function wizardSteps(): HasMany
    {
        return $this->hasMany(BrandListingWizardStep::class)->orderBy('sort_order')->orderBy('id');
    }

    public function featuredImageUrl(): string
    {
        return $this->featured_image
            ? Storage::disk('public')->url($this->featured_image)
            : asset(match (strtolower((string) $this->name_en)) {
                'apple' => 'images/brands/apple.webp',
                'samsung' => 'images/brands/samsung.webp',
                'xiaomi' => 'images/brands/xiaomi.webp',
                'huawei' => 'images/brands/huawei.webp',
                'nokia' => 'images/brands/nokia.webp',
                'sony' => 'images/brands/sony.webp',
                'lg' => 'images/brands/lg.webp',
                'google' => 'images/brands/google.webp',
                'oneplus' => 'images/brands/oneplus.webp',
                'oppo' => 'images/brands/oppo.webp',
                'vivo' => 'images/brands/vivo.webp',
                'realme' => 'images/brands/realme.webp',
                'asus' => 'images/brands/asus.webp',
                'motorola' => 'images/brands/motorola.webp',
                default => 'images/brand-default.png',
            });
    }
}
