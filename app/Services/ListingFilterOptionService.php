<?php

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\ListingAttributeValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListingFilterOptionService
{
    /**
     * Build filter metadata and only expose values that exist in the supplied listing scope.
     * Each attribute is calculated against all other selected filters, so a capacity selection
     * can immediately narrow the available colors without hiding the selected capacity itself.
     */
    public function payload(iterable $attributes, Builder $scope, array $filters = [], bool $dynamic = true): array
    {
        $attributes = collect($attributes);
        $attributeMap = $attributes->keyBy('id');

        return $attributes->map(function (Attribute $attribute) use ($scope, $filters, $attributeMap, $dynamic): array {
            $payload = [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'type' => $attribute->type->value,
                'unit' => $attribute->unit,
                'options' => $attribute->options ?? [],
                'is_filterable' => (bool) $attribute->is_filterable,
                'is_required' => (bool) ($attribute->pivot->is_required ?? $attribute->is_required),
                'sort_order' => $attribute->sort_order,
            ];

            if (! $dynamic) {
                return $payload;
            }

            $filteredScope = clone $scope;
            $this->applyFiltersExcept($filteredScope, $filters, $attributeMap, $attribute->id);
            $payload['values'] = $this->valuesFor($attribute, $filteredScope);

            return $payload;
        })->values()->all();
    }

    private function valuesFor(Attribute $attribute, Builder $scope): array
    {
        $rows = ListingAttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->whereIn('listing_id', (clone $scope)->select('id'))
            ->get(['value_string', 'value_integer', 'value_decimal', 'value_boolean', 'value_json']);

        $values = $rows->flatMap(function (ListingAttributeValue $row) use ($attribute): array {
            if ($attribute->type === AttributeType::MultiSelect) {
                return array_values((array) ($row->value_json ?? []));
            }

            $value = match ($attribute->type) {
                AttributeType::Integer => $row->value_integer ?? $row->value_string,
                AttributeType::Decimal => $row->value_decimal ?? $row->value_string,
                AttributeType::Boolean => $row->value_boolean ? '1' : '0',
                default => $row->value_string ?? $row->value_integer ?? $row->value_decimal,
            };

            return $value === null || $value === '' ? [] : [$value];
        })->filter(fn ($value) => $value !== null && $value !== '')->unique(fn ($value) => (string) $value);

        if (in_array($attribute->type, [AttributeType::Integer, AttributeType::Decimal], true)) {
            $values = $values->sortBy(fn ($value) => (float) $value);
        } else {
            $values = $values->sortBy(fn ($value) => (string) $value);
        }

        return $values->values()->all();
    }

    private function applyFiltersExcept(Builder $query, array $filters, Collection $attributes, int $exceptId): void
    {
        foreach ($filters as $attributeId => $rawValue) {
            $attributeId = (int) $attributeId;
            if ($attributeId === $exceptId) {
                continue;
            }

            $attribute = $attributes->get($attributeId);
            $values = array_values(array_filter((array) $rawValue, fn ($value) => $value !== null && $value !== ''));
            if (! $attribute || $values === []) {
                continue;
            }

            $query->whereHas('attributeValues', function (Builder $valueQuery) use ($attribute, $values): void {
                $valueQuery->where('attribute_id', $attribute->id);

                if ($attribute->type === AttributeType::MultiSelect) {
                    foreach ($values as $value) {
                        if (in_array($value, ['', null], true)) {
                            continue;
                        }

                        if (in_array($valueQuery->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                            $valueQuery->whereRaw('JSON_SEARCH(value_json, \'one\', ?) IS NOT NULL', [$value]);
                        } else {
                            $valueQuery->whereJsonContains('value_json', $value);
                        }
                    }

                    return;
                }

                $value = $values[0];
                match ($attribute->type) {
                    AttributeType::Integer => $valueQuery->where('value_integer', (int) $value),
                    AttributeType::Decimal => $valueQuery->where('value_decimal', (float) $value),
                    AttributeType::Boolean => $valueQuery->where('value_boolean', filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                    default => $valueQuery->where(function (Builder $query) use ($value): void {
                        $query->where('value_string', (string) $value);

                        // Some older listings were stored as numeric values before an
                        // attribute was changed from integer to select in the catalog.
                        if (is_numeric($value)) {
                            $query->orWhere('value_integer', (int) $value)
                                ->orWhere('value_decimal', (float) $value);
                        }
                    }),
                };
            });
        }
    }
}
