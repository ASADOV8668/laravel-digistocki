<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $configured = DB::table('attributes')
                ->whereNull('slug')
                ->where('name', 'پارت نامبر')
                ->where('type', 'select')
                ->where('is_active', true)
                ->whereExists(fn ($query) => $query->select('id')->from('brand_listing_wizard_steps')
                    ->whereColumn('attribute_id', 'attributes.id')
                    ->where('step_type', 'attribute')
                    ->where('display_type', 'swiper'))
                ->limit(2)->pluck('id');

            $now = now();
            DB::table('attributes')
                ->where('slug', 'part_number')
                ->where('name', 'پارت نامبر')
                ->where('type', 'string')
                ->where('sort_order', 100)
                ->whereNull('options')
                ->whereNotExists(fn ($query) => $query->select('id')->from('brand_listing_wizard_steps')
                    ->whereColumn('attribute_id', 'attributes.id'))
                ->update(['slug' => 'legacy_part_number', 'is_active' => false, 'updated_at' => $now]);

            if ($configured->count() === 1 && ! DB::table('attributes')->where('slug', 'part_number')->exists()) {
                DB::table('attributes')->where('id', $configured->first())->update(['slug' => 'part_number', 'updated_at' => $now]);
            }
        });
    }

    public function down(): void
    {
        // Preserve the configured attribute and any values already submitted.
    }
};
