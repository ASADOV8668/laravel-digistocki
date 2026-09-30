<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('brands')
            ->where('name_en', 'Apple')
            ->where('name', 'آیفون')
            ->update(['name' => 'اپل', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('brands')
            ->where('name_en', 'Apple')
            ->where('name', 'اپل')
            ->update(['name' => 'آیفون', 'updated_at' => now()]);
    }
};
