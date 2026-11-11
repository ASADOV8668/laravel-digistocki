<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $groups = [
            'apple' => [
                ['name' => 'SE', 'pattern' => '/\biPhone\s+SE\b/i'],
                ['name' => '11', 'pattern' => '/\biPhone\s+11\b/i'],
                ['name' => '12', 'pattern' => '/\biPhone\s+12\b/i'],
                ['name' => '13', 'pattern' => '/\biPhone\s+13\b/i'],
                ['name' => '14', 'pattern' => '/\biPhone\s+14\b/i'],
                ['name' => '15', 'pattern' => '/\biPhone\s+15\b/i'],
                ['name' => '16', 'pattern' => '/\biPhone\s+16\b/i'],
            ],
            'samsung' => [
                ['name' => 'S', 'pattern' => '/\bGalaxy\s+S/i'],
                ['name' => 'A', 'pattern' => '/\bGalaxy\s+A/i'],
                ['name' => 'Z', 'pattern' => '/\bGalaxy\s+Z/i'],
                ['name' => 'Note', 'pattern' => '/\bGalaxy\s+Note/i'],
            ],
            'xiaomi' => [
                ['name' => 'Redmi Note', 'pattern' => '/\bRedmi\s+Note/i'],
                ['name' => 'Redmi', 'pattern' => '/\bRedmi\b/i'],
                ['name' => 'POCO', 'pattern' => '/\bPOCO\b/i'],
                ['name' => 'Mi', 'pattern' => '/\bMi\s+/i'],
            ],
        ];

        foreach ($groups as $brandSlug => $definitions) {
            $brand = DB::table('brands')->where('slug', $brandSlug)->first();
            if (! $brand) {
                continue;
            }

            foreach ($definitions as $sort => $definition) {
                $seriesId = DB::table('phone_series')->where('brand_id', $brand->id)->where('slug', Str::slug($definition['name']))->value('id');
                if (! $seriesId) {
                    $seriesId = DB::table('phone_series')->insertGetId([
                        'brand_id' => $brand->id,
                        'name' => $definition['name'],
                        'name_fa' => $definition['name'],
                        'name_en' => $definition['name'],
                        'slug' => Str::slug($definition['name']),
                        'is_active' => true,
                        'sort_order' => $sort + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $models = DB::table('phone_models')->where('brand_id', $brand->id)->get(['id', 'name', 'name_en']);
                foreach ($models as $model) {
                    if (preg_match($definition['pattern'], (string) ($model->name_en ?: $model->name))) {
                        DB::table('phone_models')->where('id', $model->id)->update(['phone_series_id' => $seriesId, 'updated_at' => now()]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $brandIds = DB::table('brands')->whereIn('slug', ['apple', 'samsung', 'xiaomi'])->pluck('id');
        if ($brandIds->isEmpty()) {
            return;
        }

        DB::table('phone_models')->whereIn('brand_id', $brandIds)->update(['phone_series_id' => null]);
        DB::table('phone_series')->whereIn('brand_id', $brandIds)->delete();
    }
};
