<?php

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\PhoneModel;

class ListingAttributeValidator
{
    /** @return array<string, string> */
    public function errors(?PhoneModel $model, array $values): array
    {
        if (! $model) {
            return [];
        }

        $allowed = $model->attributes()->where('attributes.is_active', true)->get()->keyBy('id');
        $errors = [];

        foreach ($allowed as $attribute) {
            if ((bool) $attribute->pivot->is_required && $this->isEmpty($values[$attribute->id] ?? null)) {
                $errors['attributes.'.$attribute->id] = 'وارد کردن این ویژگی الزامی است.';
            }
        }

        foreach ($values as $attributeId => $value) {
            $attribute = $allowed->get((int) $attributeId);
            $field = 'attributes.'.(string) $attributeId;

            if (! $attribute instanceof Attribute) {
                $errors[$field] = 'ویژگی انتخاب‌شده برای این مدل معتبر نیست.';
                continue;
            }

            if ($this->isEmpty($value)) {
                continue;
            }

            $message = match ($attribute->type) {
                AttributeType::Integer => $this->isInteger($value) ? null : 'مقدار باید عدد صحیح باشد.',
                AttributeType::Decimal => is_numeric($value) && ! is_array($value) ? null : 'مقدار باید عددی باشد.',
                AttributeType::Boolean => $this->isBoolean($value) ? null : 'مقدار بولی نامعتبر است.',
                AttributeType::Select => $this->inOptions($value, $attribute) ? null : 'گزینه انتخاب‌شده معتبر نیست.',
                AttributeType::MultiSelect => $this->validMultiSelect($value, $attribute) ? null : 'گزینه‌های انتخاب‌شده معتبر نیستند.',
                AttributeType::String => is_scalar($value) ? null : 'مقدار متنی نامعتبر است.',
            };

            if ($message !== null) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }

    private function isInteger(mixed $value): bool
    {
        return ! is_array($value) && filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function isBoolean(mixed $value): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false', 'on', 'off', 'yes', 'no'], true);
    }

    private function inOptions(mixed $value, Attribute $attribute): bool
    {
        return ! is_array($value) && in_array((string) $value, array_map('strval', $attribute->options ?? []), true);
    }

    private function validMultiSelect(mixed $value, Attribute $attribute): bool
    {
        if (! is_array($value) || count($value) !== count(array_unique($value, SORT_REGULAR))) {
            return false;
        }

        $options = array_map('strval', $attribute->options ?? []);

        foreach ($value as $item) {
            if (! is_scalar($item) || ! in_array((string) $item, $options, true)) {
                return false;
            }
        }

        return true;
    }
}
