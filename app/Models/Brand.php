<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_en', 'slug', 'logo', 'is_active'];

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
}
