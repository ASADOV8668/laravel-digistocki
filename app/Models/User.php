<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, InteractsWithMedia, Notifiable;

    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
        'can_post_listings' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'mobile',
        'national_id',
        'email',
        'password',
        'password_set_at',
        'can_post_listings',
        'listing_import_parser_id',
    ];

    public const ADMINISTRABLE_ROLES = [
        'user' => 'کاربر عادی',
        'admin' => 'مدیر سیستم',
        'technical' => 'مدیر فنی',
        'accounting' => 'مدیر حسابداری',
    ];

    public function administrationRole(): string
    {
        return $this->isAdmin() ? 'admin' : ($this->department_role ?? 'user');
    }

    public static function attributesForAdministrationRole(string $role): array
    {
        return [
            'role' => $role === 'admin' ? 'admin' : 'user',
            'department_role' => in_array($role, ['technical', 'accounting'], true) ? $role : null,
        ];
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? 'user') === 'admin';
    }

    public function isSeniorAdmin(): bool
    {
        return $this->isAdmin() && $this->is_senior_admin;
    }

    public function isUser(): bool
    {
        return ! $this->isAdmin();
    }

    public function isSeller(): bool
    {
        return $this->storefront?->isApproved() === true;
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function storefront(): HasOne
    {
        return $this->hasOne(SellerStore::class);
    }

    public function listingImportParser(): BelongsTo
    {
        return $this->belongsTo(ListingImportParser::class);
    }

    public function canImportListings(): bool
    {
        return $this->listingImportParser?->is_active === true;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'is_senior_admin' => 'boolean',
            'can_post_listings' => 'boolean',
            'password' => 'hashed',
            'password_set_at' => 'datetime',
        ];
    }

    public function hasSetPassword(): bool
    {
        return $this->password_set_at !== null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('listing-images')->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (! extension_loaded('imagick') && ! function_exists('imagecreatefromstring')) {
            return;
        }

        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 400)
            ->format('webp')
            ->quality(85)
            ->nonQueued();
    }
}
