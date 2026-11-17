<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('listing_import_parsers')->where('key', 'fratel')->update([
            'sample_text' => "IPHONE STOCK\n‼️17 Pro Max 512 ZAA‼️\n🔋100% 🔵465.000(✅)\n🔋93% 🤍475.000(✅)\n‼️16 Pro 256 ZAA‼️\n🔋90% ⚫335.000(🚫)",
        ]);
    }

    public function down(): void
    {
        DB::table('listing_import_parsers')->where('key', 'fratel')->update([
            'sample_text' => "IPHONE STOCK\n‼️17 Pro Max 512 ZAA‼️\n🔋100% 🔵465.000(✅)\n🔋93% 🤍475.000(✅)\n‼️16 Pro 256 ZAA‼️\n🔋90% ⚫335.000(🚫)",
        ]);
    }
};
