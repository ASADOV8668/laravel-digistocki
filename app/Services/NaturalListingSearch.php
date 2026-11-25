<?php

namespace App\Services;

use App\Models\PhoneModel;
use App\Support\PersianSearch;
use App\Support\RegistrationStatus;
use Illuminate\Database\Eloquent\Builder;

class NaturalListingSearch
{
    private const COLORS = [
        'ابی' => 'آبی', 'ابی اسمانی' => 'آبی آسمانی', 'ابی اقیانوسی' => 'آبی اقیانوسی',
        'مشکی' => 'مشکی', 'سیاه' => 'مشکی', 'سفید' => 'سفید', 'سبز' => 'سبز',
        'بنفش' => 'بنفش', 'خاکستری' => 'خاکستری', 'قرمز' => 'قرمز', 'صورتی' => 'صورتی',
        'نقره ای' => 'نقره‌ای', 'طلایی' => 'طلایی', 'نارنجی' => 'نارنجی',
        'blue' => 'آبی', 'black' => 'مشکی', 'white' => 'سفید',
    ];

    public function parse(string $input): ?array
    {
        $input = $this->normalize($input);
        if (! preg_match('/\b(?:iphone|galaxy|samsung|redmi|poco|xiaomi|pixel)\b/u', $input) || mb_strlen($input) > 160) {
            return null;
        }

        $best = '';
        $id = null;
        foreach (PhoneModel::query()->where('is_active', true)->get(['id', 'name', 'name_en', 'name_fa']) as $model) {
            foreach (array_filter([$model->name, $model->name_en, $model->name_fa]) as $name) {
                $name = $this->normalize($name);
                if ($name !== '' && mb_strlen($name) > mb_strlen($best)
                    && ($input === $name || str_starts_with($input, $name.' '))) {
                    $best = $name;
                    $id = $model->id;
                }
            }
        }
        if ($id === null) {
            return null;
        }

        $remaining = trim(mb_substr($input, mb_strlen($best)));
        $filters = ['phone_model_id' => $id];
        if (preg_match('/(?:^| )(32|64|128|256|512|1024|2048)(?:\s*(?:gb|g|گیگابایت|گیگ))?(?= |$)/u', $remaining, $match, PREG_OFFSET_CAPTURE)) {
            $filters['storage'] = (int) $match[1][0];
            $remaining = substr_replace($remaining, ' ', $match[0][1], strlen($match[0][0]));
        }
        if (preg_match('/بدون رجیستری?|رجیستر(?:ی)? (?:نشده|نیست|ندارد)|not registered|unregistered|رجیستر(?:ی)?(?: (?:شده|دارد))?|\bregistered\b/u', $remaining, $match, PREG_OFFSET_CAPTURE)) {
            $filters['registered'] = RegistrationStatus::registered(RegistrationStatus::detect($match[0][0])) ?? true;
            $remaining = substr_replace($remaining, ' ', $match[0][1], strlen($match[0][0]));
        }

        $remaining = trim(preg_replace('/ +/', ' ', $remaining));
        if (isset(self::COLORS[$remaining])) {
            $filters['color'] = self::COLORS[$remaining];
            $remaining = '';
        }

        return $remaining === '' ? $filters : null;
    }

    public function apply(Builder $query, array $criteria): Builder
    {
        $query->where('phone_model_id', $criteria['phone_model_id']);
        if (isset($criteria['storage'])) {
            $query->whereHas('attributeValues', fn (Builder $values) => $values->whereRelation('attribute', 'slug', 'storage')
                ->where(fn (Builder $storage) => $storage->where('value_integer', $criteria['storage'])->orWhere('value_string', (string) $criteria['storage'])));
        }
        if (isset($criteria['color'])) {
            $query->whereHas('attributeValues', function (Builder $values) use ($criteria): void {
                $values->whereRelation('attribute', 'slug', 'color');
                if ($criteria['color'] === 'آبی') {
                    $values->where(fn (Builder $blue) => $blue->where('value_string', 'like', 'آبی%')->orWhereRaw('LOWER(value_string) LIKE ?', ['%blue%']));
                } else {
                    $values->where('value_string', $criteria['color']);
                }
            });
        }
        if (isset($criteria['registered'])) {
            $statuses = $criteria['registered']
                ? [RegistrationStatus::REGISTERED, RegistrationStatus::OWNER, RegistrationStatus::OTHER, 'رجیستر شرکتی', 'Registered']
                : [RegistrationStatus::UNREGISTERED, 'بدون رجیستر', 'بدون رجیستری', 'Unregistered'];
            $query->where(fn (Builder $registry) => $registry
                ->whereHas('attributeValues', fn (Builder $value) => $value->whereRelation('attribute', 'slug', 'registered')->where('value_boolean', $criteria['registered']))
                ->orWhereHas('attributeValues', fn (Builder $value) => $value->whereRelation('attribute', 'slug', 'registration_status')->whereIn('value_string', $statuses)));
        }

        return $query;
    }

    private function normalize(string $input): string
    {
        $input = mb_strtolower(PersianSearch::normalize($input));
        $input = strtr($input, ['+' => ' plus', 'ایفون' => 'iphone', 'گلکسی' => 'galaxy', 'ردمی' => 'redmi', 'پوکو' => 'poco',
            'پرومکس' => 'pro max', 'پرو' => 'pro', 'مکس' => 'max', 'پلاس' => 'plus', 'مینی' => 'mini']);

        return trim(preg_replace('/[^\pL\pN]+/u', ' ', $input));
    }
}
