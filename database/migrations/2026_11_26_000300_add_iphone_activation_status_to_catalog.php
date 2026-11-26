<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $attributeId = DB::table('attributes')->where('slug', 'activation_status')->value('id')
            ?? DB::table('attributes')->insertGetId([
                'name' => 'وضعیت فعال‌سازی', 'slug' => 'activation_status', 'type' => 'select',
                'options' => json_encode(['اکتیو', 'نات اکتیو'], JSON_UNESCAPED_UNICODE),
                'is_active' => true, 'is_required' => false, 'is_filterable' => false,
                'sort_order' => 46, 'created_at' => $now, 'updated_at' => $now,
            ]);

        DB::table('phone_models')
            ->where('device_type', 'mobile')
            ->whereIn('brand_id', DB::table('brands')->where('slug', 'apple')->select('id'))
            ->select('id')
            ->chunkById(100, function ($models) use ($attributeId, $now): void {
                DB::table('model_attributes')->insertOrIgnore($models->map(fn ($model) => [
                    'phone_model_id' => $model->id, 'attribute_id' => $attributeId,
                    'is_required' => false, 'sort_order' => 46, 'created_at' => $now, 'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        // Preserve catalog data and activation statuses already saved on listings.
    }
};
