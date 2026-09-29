<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value_string')->nullable();
            $table->integer('value_integer')->nullable();
            $table->decimal('value_decimal', 12, 2)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();
            $table->unique(['listing_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_attribute_values');
    }
};
