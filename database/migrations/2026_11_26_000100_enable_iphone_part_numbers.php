<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $modelIds = DB::table('phone_models')
            ->join('brands', 'brands.id', '=', 'phone_models.brand_id')
            ->where('brands.slug', 'apple')
            ->where('phone_models.device_type', 'mobile')
            ->pluck('phone_models.id');

        if ($modelIds->isEmpty()) {
            return;
        }

        $now = now();
        $attributeId = DB::table('attributes')->where('slug', 'part_number')->value('id') ?? DB::table('attributes')->insertGetId([
            'slug' => 'part_number',
            'name' => 'پارت نامبر',
            'type' => 'string',
            'sort_order' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($modelIds->chunk(200) as $ids) {
            DB::table('model_attributes')->insertOrIgnore($ids->map(fn ($modelId) => [
                'phone_model_id' => $modelId,
                'attribute_id' => $attributeId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }

    public function down(): void
    {
        // Keep catalog assignments and part numbers entered by sellers.
    }
};
