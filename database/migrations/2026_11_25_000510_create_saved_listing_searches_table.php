<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_listing_searches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phone_model_id')->constrained()->cascadeOnDelete();
            $table->string('query', 160);
            $table->string('fingerprint', 64);
            $table->json('criteria');
            $table->timestamps();
            $table->unique(['user_id', 'fingerprint']);
            $table->index('phone_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_listing_searches');
    }
};
