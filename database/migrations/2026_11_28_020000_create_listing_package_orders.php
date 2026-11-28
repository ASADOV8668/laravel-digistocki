<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_package_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('seller_store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('listing_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('package_name', 120);
            $table->string('store_name');
            $table->unsignedInteger('publication_quota');
            $table->unsignedInteger('active_listing_limit');
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedBigInteger('amount_toman');
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_package_orders');
    }
};
