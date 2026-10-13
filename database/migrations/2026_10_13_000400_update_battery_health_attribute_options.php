<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attributes')
            ->where('slug', 'battery_health')
            ->update([
                'type' => 'integer',
                'options' => json_encode(array_values(array_unique(array_merge(range(0, 50, 10), range(55, 100, 5)))), JSON_THROW_ON_ERROR),
            ]);
    }

    public function down(): void
    {
        DB::table('attributes')
            ->where('slug', 'battery_health')
            ->update(['options' => null]);
    }
};
