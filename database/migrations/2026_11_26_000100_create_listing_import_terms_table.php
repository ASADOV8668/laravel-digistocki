<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_import_terms', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_bin';
            $table->id();
            $table->string('emoji', 64)->unique();
            $table->string('meaning', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_import_terms');
    }
};
