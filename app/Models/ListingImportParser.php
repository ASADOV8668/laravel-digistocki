<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingImportParser extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'title', 'class_name', 'pattern', 'symbol_map', 'sample_text', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'symbol_map' => 'array'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
