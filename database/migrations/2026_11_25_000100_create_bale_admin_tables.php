<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bale_admin_connections', function (Blueprint $table) {
            $table->string('bale_user_id', 20)->primary();
            $table->string('chat_id', 20);
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('code_hash', 64)->nullable()->unique();
            $table->timestamp('code_expires_at')->nullable();
        });

        Schema::create('bale_admin_actions', function (Blueprint $table) {
            $table->string('update_id', 20)->primary();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('listing_id');
            $table->string('action', 16);
            $table->text('reason')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bale_admin_actions');
        Schema::dropIfExists('bale_admin_connections');
    }
};
