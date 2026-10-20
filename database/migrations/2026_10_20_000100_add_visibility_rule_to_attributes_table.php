<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            $table->string('visibility_rule', 20)->default('all')->after('is_active');
            $table->json('visibility_model_ids')->nullable()->after('visibility_rule');
            $table->index('visibility_rule');
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            $table->dropIndex(['visibility_rule']);
            $table->dropColumn(['visibility_rule', 'visibility_model_ids']);
        });
    }
};
