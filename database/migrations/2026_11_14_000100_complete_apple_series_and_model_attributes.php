<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phone_series') || ! Schema::hasTable('phone_models')) {
            return;
        }

        $brand = DB::table('brands')->where('slug', 'apple')->first();
        if (! $brand) {
            return;
        }

        $groups = [
            ['name' => 'X', 'name_fa' => 'X', 'sort_order' => 2, 'models' => ['iPhone X', 'iPhone XR', 'iPhone XS', 'iPhone XS Max']],
            ['name' => '17', 'name_fa' => '۱۷', 'sort_order' => 9, 'models' => ['iPhone 17', 'iPhone 17 Air', 'iPhone 17 Pro', 'iPhone 17 Pro Max']],
            ['name' => '18', 'name_fa' => '۱۸', 'sort_order' => 10, 'models' => ['iPhone 18', 'iPhone 18 Pro', 'iPhone 18 Pro Max']],
            ['name' => 'Duo', 'name_fa' => 'دوو', 'sort_order' => 11, 'models' => ['iPhone Duo']],
        ];

        foreach ($groups as $group) {
            $slug = Str::slug($group['name']);
            $seriesId = DB::table('phone_series')
                ->where('brand_id', $brand->id)
                ->where('slug', $slug)
                ->value('id');

            if (! $seriesId) {
                $seriesId = DB::table('phone_series')->insertGetId([
                    'brand_id' => $brand->id,
                    'name' => $group['name'],
                    'name_fa' => $group['name_fa'],
                    'name_en' => $group['name'],
                    'slug' => $slug,
                    'is_active' => true,
                    'sort_order' => $group['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($group['models'] as $modelName) {
                $model = DB::table('phone_models')
                    ->where('brand_id', $brand->id)
                    ->where('slug', Str::slug($modelName))
                    ->first();

                if (! $model) {
                    DB::table('phone_models')->insert([
                        'brand_id' => $brand->id,
                        'phone_series_id' => $seriesId,
                        'name' => $modelName,
                        'name_fa' => str_replace('iPhone', 'آیفون', $modelName),
                        'name_en' => $modelName,
                        'slug' => Str::slug($modelName),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('phone_models')->where('id', $model->id)->update([
                        'phone_series_id' => $seriesId,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $appleModelIds = DB::table('phone_models')->where('brand_id', $brand->id)->pluck('id');
        $excludedAttributeIds = DB::table('attributes')->whereIn('slug', ['ram', 'infrared'])->pluck('id');
        $allowedAttributeIds = DB::table('attributes')
            ->where('is_active', true)
            ->when($excludedAttributeIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $excludedAttributeIds))
            ->pluck('id');

        if ($appleModelIds->isNotEmpty() && $excludedAttributeIds->isNotEmpty()) {
            DB::table('model_attributes')
                ->whereIn('phone_model_id', $appleModelIds)
                ->whereIn('attribute_id', $excludedAttributeIds)
                ->delete();

            if (Schema::hasTable('brand_listing_wizard_steps')) {
                DB::table('brand_listing_wizard_steps')
                    ->where('brand_id', $brand->id)
                    ->whereIn('attribute_id', $excludedAttributeIds)
                    ->delete();
            }
        }

        $newModelIds = DB::table('phone_models')
            ->where('brand_id', $brand->id)
            ->whereIn('name_en', array_merge(...array_column($groups, 'models')))
            ->pluck('id');

        foreach ($newModelIds as $modelId) {
            foreach ($allowedAttributeIds as $sortOrder => $attributeId) {
                DB::table('model_attributes')->updateOrInsert(
                    ['phone_model_id' => $modelId, 'attribute_id' => $attributeId],
                    ['is_required' => false, 'sort_order' => $sortOrder, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        }
    }

    public function down(): void
    {
        $brandId = DB::table('brands')->where('slug', 'apple')->value('id');
        if (! $brandId) {
            return;
        }

        $modelNames = [
            'iPhone X', 'iPhone XR', 'iPhone XS', 'iPhone XS Max',
            'iPhone 17', 'iPhone 17 Air', 'iPhone 17 Pro', 'iPhone 17 Pro Max',
            'iPhone 18', 'iPhone 18 Pro', 'iPhone 18 Pro Max', 'iPhone Duo',
        ];

        DB::table('phone_models')->where('brand_id', $brandId)->whereIn('name_en', $modelNames)->delete();
        DB::table('phone_series')->where('brand_id', $brandId)->whereIn('slug', ['x', '17', '18', 'duo'])->delete();
    }
};
