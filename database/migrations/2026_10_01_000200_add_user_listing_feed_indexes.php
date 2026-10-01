<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'created_at'], 'listings_user_status_created_index');
            $table->index(['user_id', 'status', 'published_at', 'expires_at'], 'listings_user_feed_index');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropIndex('listings_user_status_created_index');
            $table->dropIndex('listings_user_feed_index');
        });
    }
};
