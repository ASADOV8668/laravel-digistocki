<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_senior_admin')->default(false);
        });

        Schema::create('province_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('province_id');
            $table->string('province_name');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('reason');
            $table->string('status', 16)->default('pending')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['province_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('province_deletion_requests');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_senior_admin');
        });
    }
};
