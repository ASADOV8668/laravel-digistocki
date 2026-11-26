<?php

use App\Support\ListingTitle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $colorId = DB::table('attributes')->where('slug', 'color')->value('id');
        if ($colorId === null) {
            return;
        }

        DB::table('listings')
            ->join('listing_attribute_values as colors', 'colors.listing_id', '=', 'listings.id')
            ->where('colors.attribute_id', $colorId)
            ->whereNotNull('colors.value_string')
            ->whereIn('listings.status', ['approved', 'sold', 'expired'])
            ->select('listings.id', 'listings.title', 'colors.value_string')
            ->chunkById(200, function ($listings): void {
                foreach ($listings as $listing) {
                    $title = ListingTitle::withoutColor($listing->title, $listing->value_string);
                    if ($title !== $listing->title) {
                        DB::table('listings')->where('id', $listing->id)->where('title', $listing->title)->update(['title' => $title]);
                    }
                }
            }, 'listings.id', 'id');
    }

    public function down(): void
    {
        // Keep the published titles and any changes made after publication.
    }
};
