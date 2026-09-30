<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileOtp extends Model
{
    use HasFactory;

    protected $fillable = ['mobile', 'purpose', 'code_hash', 'attempts', 'expires_at', 'verified_at'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'expires_at' => 'datetime', 'verified_at' => 'datetime'];
    }
}
