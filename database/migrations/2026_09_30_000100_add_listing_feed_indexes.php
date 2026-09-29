<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->index(['status', 'published_at', 'expires_at'], 'listings_public_feed_index');
        });

        Schema::table('listing_attribute_values', function (Blueprint $table) {
            $table->index(['attribute_id', 'value_string'], 'listing_attribute_string_filter_index');
            $table->index(['attribute_id', 'value_integer'], 'listing_attribute_integer_filter_index');
            $table->index(['attribute_id', 'value_decimal'], 'listing_attribute_decimal_filter_index');
            $table->index(['attribute_id', 'value_boolean'], 'listing_attribute_boolean_filter_index');
        });
    }

    public function down(): void
    {
        Schema::table('listing_attribute_values', function (Blueprint $table) {
            $table->dropIndex('listing_attribute_string_filter_index');
            $table->dropIndex('listing_attribute_integer_filter_index');
            $table->dropIndex('listing_attribute_decimal_filter_index');
            $table->dropIndex('listing_attribute_boolean_filter_index');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('listings_public_feed_index');
        });
    }
};
