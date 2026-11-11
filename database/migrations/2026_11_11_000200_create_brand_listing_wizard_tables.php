<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('brands', 'can_post_listings')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->boolean('can_post_listings')->default(true)->after('is_active')->index();
            });
        }

        if (! Schema::hasTable('phone_series')) {
            Schema::create('phone_series', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('name_fa')->nullable();
                $table->string('name_en')->nullable();
                $table->string('slug');
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['brand_id', 'slug']);
            });
        }

        if (! Schema::hasColumn('phone_models', 'phone_series_id')) {
            Schema::table('phone_models', function (Blueprint $table): void {
                $table->foreignId('phone_series_id')->nullable()->after('brand_id')->constrained('phone_series')->nullOnDelete();
                $table->index(['brand_id', 'phone_series_id']);
            });
        }

        if (! Schema::hasTable('brand_listing_wizard_steps')) {
            Schema::create('brand_listing_wizard_steps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
                $table->string('step_type', 30);
                $table->foreignId('attribute_id')->nullable()->constrained('attributes')->nullOnDelete();
                $table->string('display_type', 20)->default('buttons');
                $table->boolean('is_required')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['brand_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_listing_wizard_steps');

        if (Schema::hasColumn('phone_models', 'phone_series_id')) {
            Schema::table('phone_models', function (Blueprint $table): void {
                $table->dropForeign(['phone_series_id']);
                $table->dropIndex(['brand_id', 'phone_series_id']);
                $table->dropColumn('phone_series_id');
            });
        }

        Schema::dropIfExists('phone_series');

        if (Schema::hasColumn('brands', 'can_post_listings')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->dropIndex(['can_post_listings']);
                $table->dropColumn('can_post_listings');
            });
        }
    }
};
