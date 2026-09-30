<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'brand_id', 'phone_model_id', 'province_id', 'city_id', 'title', 'slug', 'description', 'price', 'is_negotiable', 'status', 'rejection_reason', 'views_count', 'published_at', 'expires_at'];

    protected function casts(): array
    {
        return ['status' => ListingStatus::class, 'price' => 'integer', 'is_negotiable' => 'boolean', 'views_count' => 'integer', 'published_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function phoneModel(): BelongsTo
    {
        return $this->belongsTo(PhoneModel::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_primary', true)->orderBy('sort_order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ListingAttributeValue::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'listing_attribute_values')->withPivot(['value_string', 'value_integer', 'value_decimal', 'value_boolean', 'value_json'])->withTimestamps();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Approved);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->approved()->where(function (Builder $query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [ListingStatus::Pending->value, ListingStatus::Approved->value])->where(function (Builder $query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isExpired(): bool
    {
        return $this->status === ListingStatus::Expired
            || ($this->status === ListingStatus::Approved && $this->expires_at?->isPast());
    }
}
