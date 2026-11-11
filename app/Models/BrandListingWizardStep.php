<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandListingWizardStep extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = ['brand_id', 'step_type', 'attribute_id', 'display_type', 'is_required', 'sort_order'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'sort_order' => 'integer'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
