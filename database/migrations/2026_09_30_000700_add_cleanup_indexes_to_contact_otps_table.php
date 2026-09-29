<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_otps', function (Blueprint $table) {
            $table->index('expires_at', 'contact_otps_expires_at_index');
            $table->index('verified_at', 'contact_otps_verified_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('contact_otps', function (Blueprint $table) {
            $table->dropIndex('contact_otps_expires_at_index');
            $table->dropIndex('contact_otps_verified_at_index');
        });
    }
};
