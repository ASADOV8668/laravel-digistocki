<?php

namespace App\Services\ListingImport;

use App\Models\Attribute;
use App\Models\Listing;
use App\Models\User;
use App\Support\PersianDigits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InventoryMatch
{
    private const FIELDS = ['storage', 'color', 'battery_health'];

    private ?array $attributeIds = null;

    public function preview(User $user, array $rows): array
    {
        $inventory = collect($rows)->contains('supported', true)
            ? $this->inventory($user)->with('attributeValues')->get()
            : collect();
        $byModel = $inventory->groupBy('phone_model_id');
        $fingerprints = $inventory->mapWithKeys(fn (Listing $listing) => [$listing->id => $this->fingerprint($this->values($listing))]);
        $incoming = array_map(fn (array $row) => $this->fingerprint($row['attributes']), $rows);
        $frequency = collect($rows)->keys()->countBy(fn (int $index) => $rows[$index]['phone_model_id'].':'.$incoming[$index]);
        $possibleIds = [];

        foreach ($rows as $index => &$row) {
            if (! $row['supported']) {
                $row['inventory_state'] = 'unsupported';

                continue;
            }

            $sameModel = $byModel->get($row['phone_model_id'], collect());
            $fingerprint = $incoming[$index];
            $candidates = $sameModel->filter(fn (Listing $listing) => $fingerprint === null
                || $fingerprints[$listing->id] === null || $fingerprints[$listing->id] === $fingerprint);
            foreach ($candidates as $listing) {
                $possibleIds[] = $listing->id;
            }

            $overlappingRows = $frequency[$row['phone_model_id'].':'.$fingerprint] > 1;
            if ($candidates->isEmpty() && ! $overlappingRows) {
                $row['inventory_state'] = 'new';

                continue;
            }
            $match = $candidates->count() === 1 ? $candidates->first() : null;
            if ($match === null || $fingerprint === null || $fingerprints[$match->id] === null || $overlappingRows) {
                $row['inventory_state'] = 'review';

                continue;
            }

            $row['inventory_state'] = $match->price === $row['price'] && ! $match->price_on_request ? 'unchanged' : 'price_change';
            $row['inventory_match'] = ['id' => $match->id, 'title' => $match->title, 'price' => $match->price, 'price_on_request' => $match->price_on_request];
        }

        $missing = $inventory->whereNotIn('id', $possibleIds)->map(fn (Listing $listing) => [
            'id' => $listing->id, 'title' => $listing->title,
        ])->values()->all();

        return [$rows, $missing];
    }

    public function updatePrice(User $user, array $source, array $row, string $errorKey): bool
    {
        $match = $source['inventory_match'] ?? null;
        $listing = $match === null ? null : $this->inventory($user)
            ->where('phone_model_id', $source['phone_model_id'])
            ->whereKey($match['id'])->lockForUpdate()->with('attributeValues')->first();
        $fingerprint = $this->fingerprint($row['attributes']);

        if ($listing === null || $fingerprint === null || $this->fingerprint($this->values($listing)) !== $fingerprint
            || $listing->price !== $match['price'] || $listing->price_on_request !== $match['price_on_request']) {
            throw ValidationException::withMessages([$errorKey => 'آگهی یا مشخصات آن تغییر کرده است. دوباره پیش‌نمایش ایمپورت را بسازید.']);
        }
        if ($listing->price === $row['price'] && ! $listing->price_on_request) {
            return false;
        }

        $listing->update(['price' => $row['price'], 'price_on_request' => false]);

        return true;
    }

    private function inventory(User $user): Builder
    {
        return Listing::query()->where('user_id', $user->id)->active()->where('is_user_disabled', false);
    }

    private function values(Listing $listing): array
    {
        return $listing->attributeValues->mapWithKeys(fn ($value) => [$value->attribute_id => $value->value_integer ?? $value->value_string])->all();
    }

    private function fingerprint(array $values): ?string
    {
        $this->attributeIds ??= Attribute::query()->whereIn('slug', self::FIELDS)->pluck('id', 'slug')->all();
        $parts = [];
        foreach (self::FIELDS as $field) {
            $value = $values[$this->attributeIds[$field] ?? 0] ?? null;
            if (! is_scalar($value) || trim((string) $value) === '') {
                return null;
            }
            $value = mb_strtolower(trim(PersianDigits::toEnglish((string) $value)));
            $parts[$field] = $field === 'color'
                ? preg_replace('/[\s\x{200C}]+/u', ' ', str_replace(['ي', 'ك'], ['ی', 'ک'], $value))
                : $value;
        }

        return json_encode($parts, JSON_UNESCAPED_UNICODE);
    }
}
