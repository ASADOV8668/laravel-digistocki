<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table): void {
            $table->foreignId('media_id')->nullable()->after('listing_id')->constrained('media')->nullOnDelete();
            $table->unique(['listing_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table): void {
            $table->dropUnique('listing_images_listing_id_media_id_unique');
            $table->dropConstrainedForeignId('media_id');
        });
    }
};
