<?php

use App\Services\ListingImport\Parsers\UnifiedParser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('listing_import_parsers')->insertOrIgnore([
            'key' => 'unified_price_list',
            'title' => 'لیست قیمت یکپارچه',
            'class_name' => UnifiedParser::class,
            'description' => 'قالب مشترک برای لیست‌های آت‌کالا، فراتل، باماتل و ترکیب ساختارهای آن‌ها.',
            'sample_text' => "💙13Pro 256g CH 92BT\n💰172/000m\n‼️17 Pro Max 512 ZAA‼️\n🔋100% 🔵465.000(✅)\n🔋93% 🤍475.000(✅)\n📱15 pro max 512G\nzaa 89% 🟦\n💵244,900\nرجیستر ✅",
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('listing_import_parsers')->where('key', 'unified_price_list')->where('class_name', UnifiedParser::class)->delete();
    }
};
