<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_attributes', function (Blueprint $table): void {
            $table->json('custom_options')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('model_attributes', function (Blueprint $table): void {
            $table->dropColumn('custom_options');
        });
    }
};
