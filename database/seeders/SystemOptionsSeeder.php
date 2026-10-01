<?php

namespace Database\Seeders;

use App\Models\SystemOption;
use App\Services\SystemOptions;
use Illuminate\Database\Seeder;

class SystemOptionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SystemOptions::DEFAULTS as $key => $value) {
            SystemOption::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => in_array($key, ['max_image_upload_mb', 'max_image_upload_count', 'listings_enabled', 'listing_duplicate_cooldown_hours'], true) ? 'integer' : 'string'],
            );
        }
    }
}
