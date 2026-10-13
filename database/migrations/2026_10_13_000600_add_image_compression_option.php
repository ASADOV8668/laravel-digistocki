<?php

use App\Models\SystemOption;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SystemOption::firstOrCreate(
            ['key' => 'image_compression_percent'],
            ['value' => '20', 'type' => 'integer'],
        );
    }

    public function down(): void
    {
        SystemOption::query()->where('key', 'image_compression_percent')->delete();
    }
};
