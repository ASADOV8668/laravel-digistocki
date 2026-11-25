<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedListingSearch extends Model
{
    protected $fillable = ['user_id', 'phone_model_id', 'query', 'fingerprint', 'criteria'];

    protected function casts(): array
    {
        return ['criteria' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
