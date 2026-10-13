<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('provinces') && ! Schema::hasColumn('provinces', 'latitude')) {
            Schema::table('provinces', function (Blueprint $table): void {
                $table->decimal('latitude', 10, 7)->nullable()->after('tel_prefix');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                $table->index(['latitude', 'longitude']);
            });
        }

        if (Schema::hasTable('cities') && ! Schema::hasColumn('cities', 'latitude')) {
            Schema::table('cities', function (Blueprint $table): void {
                $table->decimal('latitude', 10, 7)->nullable()->after('name');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                $table->index(['latitude', 'longitude']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('provinces') && Schema::hasColumn('provinces', 'latitude')) {
            Schema::table('provinces', function (Blueprint $table): void {
                $table->dropIndex('provinces_latitude_longitude_index');
                $table->dropColumn(['latitude', 'longitude']);
            });
        }

        if (Schema::hasTable('cities') && Schema::hasColumn('cities', 'latitude')) {
            Schema::table('cities', function (Blueprint $table): void {
                $table->dropIndex('cities_latitude_longitude_index');
                $table->dropColumn(['latitude', 'longitude']);
            });
        }
    }
};
