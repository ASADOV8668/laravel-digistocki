<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->unsignedInteger('publication_quota');
            $table->unsignedInteger('active_listing_limit');
            $table->unsignedSmallInteger('duration_days');
            $table->timestamps();
        });

        Schema::create('store_listing_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_package_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('publication_quota');
            $table->unsignedInteger('active_listing_limit');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_listing_packages');
        Schema::dropIfExists('listing_packages');
    }
};
