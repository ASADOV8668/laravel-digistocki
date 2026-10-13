<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('options')->where('key', 'listing_duplicate_cooldown_hours')->first();

        if (! $legacy) {
            return;
        }

        $minutesKeyExists = DB::table('options')->where('key', 'listing_duplicate_cooldown_minutes')->exists();

        if ($minutesKeyExists) {
            DB::table('options')->where('id', $legacy->id)->delete();

            return;
        }

        DB::table('options')->where('id', $legacy->id)->update([
            'key' => 'listing_duplicate_cooldown_minutes',
            'value' => (string) ((int) $legacy->value * 60),
            'type' => 'integer',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $current = DB::table('options')->where('key', 'listing_duplicate_cooldown_minutes')->first();

        if (! $current) {
            return;
        }

        DB::table('options')->where('id', $current->id)->update([
            'key' => 'listing_duplicate_cooldown_hours',
            'value' => (string) intdiv((int) $current->value, 60),
            'type' => 'integer',
            'updated_at' => now(),
        ]);
    }
};
