<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->string('listing_approval_mode', 20)
                ->default('manual')
                ->after('approval_status')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->dropIndex(['listing_approval_mode']);
            $table->dropColumn('listing_approval_mode');
        });
    }
};
