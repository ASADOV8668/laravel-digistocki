<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('listing_import_parser_id')
                ->nullable()
                ->after('can_post_listings')
                ->constrained('listing_import_parsers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['listing_import_parser_id']);
            $table->dropColumn('listing_import_parser_id');
        });
    }
};
