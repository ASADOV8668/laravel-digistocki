<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('brands')
            ->whereIn('slug', ['apple', 'samsung', 'xiaomi'])
            ->update(['can_post_listings' => true, 'updated_at' => now()]);

        DB::table('brands')
            ->whereNotIn('slug', ['apple', 'samsung', 'xiaomi'])
            ->update(['can_post_listings' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // This migration records the current business rule; no destructive
        // rollback is applied to administrator-controlled brand settings.
    }
};
