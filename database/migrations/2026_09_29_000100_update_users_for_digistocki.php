<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 11)->nullable()->unique()->after('email');
            $table->enum('role', ['user', 'admin'])->default('user')->index()->after('password');
            $table->timestamp('mobile_verified_at')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('mobile_verified_at');
            $table->boolean('is_active')->default(true)->index()->after('avatar');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['mobile']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['mobile', 'role', 'mobile_verified_at', 'avatar', 'is_active']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
