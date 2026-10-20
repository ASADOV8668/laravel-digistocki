<?php

namespace App\Models;

use App\Enums\AttributeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'type', 'unit', 'options', 'is_filterable', 'is_required', 'sort_order', 'is_active', 'visibility_rule', 'visibility_model_ids'];

    protected function casts(): array
    {
        return [
            'type' => AttributeType::class,
            'options' => 'array',
            'is_filterable' => 'boolean',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'visibility_model_ids' => 'array',
        ];
    }

    public function scopeVisibleForModel(Builder $query, int $phoneModelId): Builder
    {
        return $query->where(function (Builder $query) use ($phoneModelId): void {
            $query->where('visibility_rule', 'all')
                ->orWhere(function (Builder $query) use ($phoneModelId): void {
                    $query->where('visibility_rule', 'include')
                        ->whereJsonContains('visibility_model_ids', $phoneModelId);
                })
                ->orWhere(function (Builder $query) use ($phoneModelId): void {
                    $query->where('visibility_rule', 'exclude')
                        ->where(function (Builder $query) use ($phoneModelId): void {
                            $query->whereNull('visibility_model_ids')
                                ->orWhereJsonDoesntContain('visibility_model_ids', $phoneModelId);
                        });
                });
        });
    }

    public function isVisibleForModel(int $phoneModelId): bool
    {
        $modelIds = array_map('intval', $this->visibility_model_ids ?? []);

        return match ($this->visibility_rule ?: 'all') {
            'include' => in_array($phoneModelId, $modelIds, true),
            'exclude' => ! in_array($phoneModelId, $modelIds, true),
            default => true,
        };
    }

    public function modelAttributes(): HasMany
    {
        return $this->hasMany(ModelAttribute::class);
    }

    public function optionRestrictions(): HasMany
    {
        return $this->hasMany(AttributeOptionRestriction::class);
    }

    /** @return array<int, string> */
    public function optionsForModel(?int $phoneModelId = null): array
    {
        $options = array_values(array_map('strval', $this->options ?? []));

        if ($phoneModelId === null || ! in_array($this->type?->value, [AttributeType::Select->value, AttributeType::MultiSelect->value], true)) {
            return $options;
        }

        $matchingRules = $this->relationLoaded('optionRestrictions')
            ? $this->optionRestrictions->filter(fn (AttributeOptionRestriction $rule): bool => in_array($phoneModelId, array_map('intval', $rule->phone_model_ids ?? []), true))
            : $this->optionRestrictions()->get()->filter(fn (AttributeOptionRestriction $rule): bool => in_array($phoneModelId, array_map('intval', $rule->phone_model_ids ?? []), true));

        if ($matchingRules->isEmpty()) {
            return $options;
        }

        $allowed = $matchingRules->flatMap(fn (AttributeOptionRestriction $rule) => array_map('strval', $rule->options ?? []))->unique()->all();

        return array_values(array_filter($options, fn (string $option): bool => in_array($option, $allowed, true)));
    }

    public function phoneModels(): BelongsToMany
    {
        return $this->belongsToMany(PhoneModel::class, 'model_attributes')->withPivot(['is_required', 'sort_order'])->withTimestamps();
    }

    public function listingValues(): HasMany
    {
        return $this->hasMany(ListingAttributeValue::class);
    }
}
