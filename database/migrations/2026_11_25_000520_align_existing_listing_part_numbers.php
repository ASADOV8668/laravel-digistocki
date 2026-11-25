<?php

use App\Support\ListingTitle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $attributeIds = DB::table('attributes')->whereIn('slug', ['storage', 'part_number', 'battery_health'])->pluck('id', 'slug');
        $partId = $attributeIds['part_number'] ?? null;

        DB::table('listings')->select(['id', 'title', 'description'])
            ->where(function ($query) use ($partId): void {
                $query->where('description', 'like', 'پارت نامبر:%');
                if ($partId !== null) {
                    $query->orWhereExists(fn ($values) => $values->select('id')->from('listing_attribute_values')
                        ->whereColumn('listing_id', 'listings.id')->where('attribute_id', $partId));
                }
            })->chunkById(200, function ($listings) use ($attributeIds, $partId): void {
                $values = DB::table('listing_attribute_values')->whereIn('listing_id', $listings->pluck('id'))
                    ->whereIn('attribute_id', $attributeIds->values())->get()
                    ->groupBy('listing_id')->map(fn ($entries) => $entries->keyBy('attribute_id'));
                foreach ($listings as $listing) {
                    $attributes = $values->get($listing->id, collect());
                    $part = $attributes->get($partId)?->value_string;
                    if (! filled($part) && preg_match('/\Aپارت نامبر:\h*([A-Z0-9\/\-]{1,25})(?![\pL\pN])/iu', (string) $listing->description, $match)) {
                        $part = $match[1];
                    }
                    $storage = $attributes->get($attributeIds['storage'] ?? null);
                    $battery = $attributes->get($attributeIds['battery_health'] ?? null);
                    $title = ListingTitle::withPartNumber($listing->title, $storage?->value_integer ?? $storage?->value_string, $part, $battery?->value_integer ?? $battery?->value_string);
                    if ($title !== $listing->title) {
                        DB::table('listings')->where('id', $listing->id)->where('title', $listing->title)->update(['title' => $title]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Existing titles can be edited after alignment; do not overwrite them.
    }
};
