<?php

namespace Tests\Support;

use App\Models\Attribute;
use App\Models\PhoneModel;
use App\Models\SellerStore;
use App\Models\User;

trait BuildsListingAttributePayload
{
    protected function storeApplication(User $user): SellerStore
    {
        return SellerStore::create([
            'user_id' => $user->id,
            'slug' => 'test-store-'.$user->id,
            'name' => 'فروشگاه تست',
            'approval_status' => 'pending',
            'is_enabled' => true,
        ]);
    }

    /** @return array<int, mixed> */
    protected function requiredAttributeValues(PhoneModel $model): array
    {
        return $model->attributes()
            ->wherePivot('is_required', true)
            ->get()
            ->mapWithKeys(function (Attribute $attribute): array {
                $value = match ($attribute->type->value) {
                    'integer' => 128,
                    'decimal' => 12.5,
                    'boolean' => true,
                    'select' => $attribute->options[0] ?? 'تست',
                    'multi_select' => array_slice($attribute->options ?? [], 0, 1),
                    default => 'مقدار تست',
                };

                return [$attribute->id => $value];
            })
            ->all();
    }
}
