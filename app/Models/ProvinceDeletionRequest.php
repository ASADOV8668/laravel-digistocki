<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sadegh19b\LaravelIranCities\Models\Province;

class ProvinceDeletionRequest extends Model
{
    protected $fillable = [
        'province_id', 'province_name', 'requested_by', 'reviewed_by', 'reason', 'status', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
