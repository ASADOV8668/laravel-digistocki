<?php

namespace App\Support;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\PhoneModel;

final class ListingTitle
{
    public static function generate(?int $brandId, ?int $modelId, array $values): ?string
    {
        $brand = $brandId ? Brand::query()->find($brandId) : null;
        $model = $modelId ? PhoneModel::query()->find($modelId) : null;

        if (! $brand || ! $model || $model->brand_id !== $brand->id) {
            return null;
        }

        $attributes = $model->attributes()
            ->whereIn('slug', ['color', 'storage'])
            ->get()
            ->keyBy('slug');

        $parts = [
            $brand->name,
            PersianNumber::digits($model->name_fa ?: $model->name),
        ];

        $color = self::value($values, $attributes->get('color'));
        if (filled($color)) {
            $parts[] = (string) $color;
        }

        $storage = self::value($values, $attributes->get('storage'));
        if (filled($storage)) {
            $parts[] = PersianNumber::digits((string) $storage).' گیگابایت';
        }

        return mb_substr(trim(implode(' ', array_filter($parts))), 0, 160);
    }

    private static function value(array $values, ?Attribute $attribute): mixed
    {
        if (! $attribute) {
            return null;
        }

        $value = $values[$attribute->id] ?? null;
        if (is_array($value)) {
            return implode('، ', array_filter($value, fn (mixed $item): bool => filled($item)));
        }

        return $value;
    }
}
