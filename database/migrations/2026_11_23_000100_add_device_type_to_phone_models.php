<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_models', function (Blueprint $table): void {
            $table->string('device_type', 16)->default('mobile')->index();
        });
    }

    public function down(): void
    {
        Schema::table('phone_models', function (Blueprint $table): void {
            $table->dropIndex(['device_type']);
            $table->dropColumn('device_type');
        });
    }
};
