<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_import_terms', function (Blueprint $table) {
            $table->dropUnique(['emoji']);
            $table->char('source_hash', 64)->nullable()->unique();
            $table->foreignId('attribute_id')->nullable()->constrained()->nullOnDelete();
            $table->text('attribute_value')->nullable();
        });

        Schema::table('listing_import_terms', function (Blueprint $table) {
            $table->longText('emoji')->change();
        });

        DB::table('listing_import_terms')->chunkById(100, function ($terms): void {
            foreach ($terms as $term) {
                DB::table('listing_import_terms')->where('id', $term->id)
                    ->update(['source_hash' => hash('sha256', mb_strtolower($term->emoji))]);
            }
        });
    }

    public function down(): void
    {
        $length = DB::getDriverName() === 'sqlite' ? 'length' : 'char_length';
        if (DB::table('listing_import_terms')->whereRaw("$length(emoji) > 64")->exists()) {
            throw new RuntimeException('Shorten import terms longer than 64 characters before rolling back.');
        }

        Schema::table('listing_import_terms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attribute_id');
            $table->dropUnique(['source_hash']);
            $table->dropColumn(['source_hash', 'attribute_value']);
        });
        Schema::table('listing_import_terms', function (Blueprint $table) {
            $table->string('emoji', 64)->change();
            $table->unique('emoji');
        });
    }
};
