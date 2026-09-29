<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_models', function (Blueprint $table) {
            $table->string('name_fa')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_fa');
            $table->index('name_fa');
            $table->index('name_en');
        });
    }

    public function down(): void
    {
        Schema::table('phone_models', function (Blueprint $table) {
            $table->dropIndex(['name_fa']);
            $table->dropIndex(['name_en']);
            $table->dropColumn(['name_fa', 'name_en']);
        });
    }
};
