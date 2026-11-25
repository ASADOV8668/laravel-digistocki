<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Services\SavedListingSearchAlerts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'brand_id', 'phone_model_id', 'province_id', 'city_id', 'title', 'slug', 'description', 'price', 'price_on_request', 'is_negotiable', 'is_user_disabled', 'status', 'rejection_reason', 'views_count', 'published_at', 'expires_at'];

    protected function casts(): array
    {
        return ['status' => ListingStatus::class, 'price' => 'integer', 'previous_price' => 'integer', 'price_changed_at' => 'datetime', 'price_on_request' => 'boolean', 'is_negotiable' => 'boolean', 'is_user_disabled' => 'boolean', 'views_count' => 'integer', 'published_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (Listing $listing): void {
            if ($listing->isDirty(['price', 'price_on_request'])) {
                $listing->previous_price = $listing->getOriginal('price_on_request') ? null : $listing->getOriginal('price');
                $listing->price_changed_at = $listing->price_on_request || $listing->price === null ? null : now();
            }
        });
        static::created(function (Listing $listing): void {
            if ($listing->status === ListingStatus::Approved && ! $listing->is_user_disabled) {
                DB::afterCommit(fn () => rescue(fn () => app(SavedListingSearchAlerts::class)->send($listing->id, false)));
            }
        });
        static::updated(function (Listing $listing): void {
            if ($listing->status !== ListingStatus::Approved || $listing->is_user_disabled) {
                return;
            }
            $priceDrop = $listing->wasChanged('price') && $listing->price !== null && $listing->previous_price > $listing->price;
            $becameAvailable = $listing->wasChanged(['status', 'is_user_disabled']);
            if ($priceDrop || $becameAvailable) {
                DB::afterCommit(fn () => rescue(fn () => app(SavedListingSearchAlerts::class)->send($listing->id, $priceDrop)));
            }
        });
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
        return $this->hasMany(ListingImage::class)->with('media')->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->with('media')->where('is_primary', true)->orderBy('sort_order');
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
        return $query->approved()->where('is_user_disabled', false)->where(function (Builder $query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [ListingStatus::Pending->value, ListingStatus::Approved->value])->where(function (Builder $query) {
            $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeWithViewerFavorite(Builder $query, ?int $userId): Builder
    {
        if ($userId === null) {
            return $query;
        }

        return $query->withExists(['favorites as is_favorited' => fn (Builder $favorite) => $favorite->where('user_id', $userId)]);
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
