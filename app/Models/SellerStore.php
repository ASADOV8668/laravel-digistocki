<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;

class SellerStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'first_name',
        'last_name',
        'national_id',
        'is_enabled',
        'is_admin_disabled',
        'approval_status',
        'province_id',
        'city_id',
        'logo_path',
        'address',
        'contact_phone',
        'business_license_number',
        'business_license_expires_at',
        'business_license_path',
        'instagram_url',
        'telegram_url',
        'whatsapp_url',
        'bale_url',
        'rubika_url',
        'sort_order',
        'application_submitted_at',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_admin_disabled' => 'boolean',
            'business_license_expires_at' => 'date',
            'application_submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (SellerStore $store): void {
            Storage::disk('public')->delete(array_values(array_filter([
                $store->logo_path,
                $store->business_license_path,
            ])));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPubliclyEnabled(): bool
    {
        return $this->isApproved() && $this->is_enabled && ! $this->is_admin_disabled;
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }
}
