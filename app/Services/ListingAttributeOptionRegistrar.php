<?php

namespace App\Services;

use App\Models\Attribute;

class ListingAttributeOptionRegistrar
{
    public function remember(Attribute $attribute, mixed $value): void
    {
        if ($attribute->slug !== 'ram' || ! is_scalar($value)) {
            return;
        }

        $ram = filter_var($value, FILTER_VALIDATE_INT);
        if ($ram === false || $ram < 1) {
            return;
        }

        $options = collect($attribute->options ?? [])
            ->map(fn ($option) => (int) $option)
            ->push($ram)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($options !== ($attribute->options ?? [])) {
            $attribute->forceFill(['options' => $options])->save();
        }
    }
}
