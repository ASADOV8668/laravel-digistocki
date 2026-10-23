<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->string('exterior_image_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->dropColumn('exterior_image_path');
        });
    }
};
