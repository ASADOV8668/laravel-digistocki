<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bale_management_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('update_id', 20)->unique();
            $table->string('subject', 120);
            $table->text('body');
            $table->string('status', 16)->default('open');
            $table->timestamps();
        });

        Schema::create('bale_management_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('bale_management_requests')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('update_id', 20)->unique();
            $table->text('body');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bale_management_replies');
        Schema::dropIfExists('bale_management_requests');
    }
};
