<?php

use App\Services\ListingImport\Parsers\AtKalaParser;
use App\Services\ListingImport\Parsers\BamaTelParser;
use App\Services\ListingImport\Parsers\FratelParser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_import_parsers', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('class_name');
            $table->longText('sample_text')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('listing_import_parsers')->insert([
            [
                'key' => 'at_kala',
                'title' => 'آت کالا',
                'class_name' => AtKalaParser::class,
                'description' => 'استخراج مدل، حافظه، رنگ، پارت‌نامبر، سلامت باتری و قیمت از قالب موجودی آت کالا.',
                'sample_text' => "ایفون استوک ((( باکد )))\nموجودی جدید :\n💙13normal 128g CH 90Bt\n💰120/000m\n🖤13Pro 256g ZAA 94Bt\n💰172/000m\n💙13Promax 256g ZAA 92BT\n💰189/000m",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'fratel',
                'title' => 'فراتل',
                'class_name' => FratelParser::class,
                'description' => 'استخراج عنوان مدل از سربرگ و ویژگی‌های هر دستگاه از خطوط موجودی فراتل.',
                'sample_text' => "IPHONE STOCK\n‼️17 Pro Max 512 ZAA‼️\n🔋100% 🔵465.000(✅)\n🔋93% 🤍475.000(✅)\n‼️16 Pro 256 ZAA‼️\n🔋90% ⚫335.000(🚫)",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bamatel',
                'title' => 'باماتل',
                'class_name' => BamaTelParser::class,
                'description' => 'استخراج آگهی‌های چندخطی باماتل شامل قیمت، رنگ، سلامت باتری، رجیستری و Cycle.',
                'sample_text' => "لیست قیمت کارکرده‌ باماتل\n📱17 pro max 256G\nzaa 100%  Cycle : 62\n💵 499,900\nرجیستر ✅\n📱15 pro max 512G\nzaa 89%\n💵244,900",
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_import_parsers');
    }
};
