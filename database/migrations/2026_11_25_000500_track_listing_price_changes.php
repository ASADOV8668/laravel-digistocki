<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->unsignedBigInteger('previous_price')->nullable();
            $table->timestamp('price_changed_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropIndex(['price_changed_at']);
            $table->dropColumn(['previous_price', 'price_changed_at']);
        });
    }
};
