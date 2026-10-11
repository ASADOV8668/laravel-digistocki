<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->string('approval_status', 20)->default('pending')->after('is_admin_disabled')->index();
            $table->string('first_name', 80)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('national_id', 10)->nullable()->after('last_name')->index();
            $table->string('business_license_number', 80)->nullable()->after('contact_phone');
            $table->date('business_license_expires_at')->nullable()->after('business_license_number');
            $table->string('business_license_path')->nullable()->after('business_license_expires_at');
            $table->string('instagram_url', 255)->nullable()->after('contact_phone');
            $table->string('telegram_url', 255)->nullable()->after('instagram_url');
            $table->string('whatsapp_url', 255)->nullable()->after('telegram_url');
            $table->string('bale_url', 255)->nullable()->after('whatsapp_url');
            $table->string('rubika_url', 255)->nullable()->after('bale_url');
            $table->unsignedInteger('sort_order')->default(0)->after('rubika_url')->index();
            $table->timestamp('application_submitted_at')->nullable()->after('sort_order');
            $table->timestamp('approved_at')->nullable()->after('application_submitted_at');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });

        DB::table('seller_stores')
            ->where('is_enabled', true)
            ->update(['approval_status' => 'approved', 'approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->dropIndex(['approval_status']);
            $table->dropIndex(['national_id']);
            $table->dropIndex(['sort_order']);
            $table->dropColumn([
                'approval_status', 'first_name', 'last_name', 'national_id',
                'business_license_number', 'business_license_expires_at', 'business_license_path',
                'instagram_url', 'telegram_url', 'whatsapp_url', 'bale_url', 'rubika_url',
                'sort_order', 'application_submitted_at', 'approved_at', 'rejection_reason',
            ]);
        });
    }
};
