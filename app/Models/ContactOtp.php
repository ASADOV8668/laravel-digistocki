<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactOtp extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'listing_id', 'code_hash', 'attempts', 'expires_at', 'verified_at'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'expires_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
}
