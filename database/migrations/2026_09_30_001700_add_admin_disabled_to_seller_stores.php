<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->boolean('is_admin_disabled')->default(false)->after('is_enabled')->index();
        });
    }

    public function down(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->dropIndex(['is_admin_disabled']);
            $table->dropColumn('is_admin_disabled');
        });
    }
};
