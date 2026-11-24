<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->string('device_type', 16)->default('mobile')->index();
        });

        DB::transaction(function (): void {
            DB::table('brands')->where('device_type', 'mobile')->chunkById(100, function ($brands): void {
                foreach ($brands as $brand) {
                    $types = DB::table('phone_models')->where('brand_id', $brand->id)
                        ->distinct()->orderBy('device_type')->pluck('device_type');

                    if ($types->count() === 1 && $types->first() !== 'mobile') {
                        DB::table('brands')->where('id', $brand->id)->update(['device_type' => $types->first()]);
                        continue;
                    }

                    foreach ($types->reject(fn (string $type) => $type === 'mobile') as $type) {
                        $this->splitBrand($brand, $type);
                    }
                }
            });
        });
    }

    public function down(): void
    {
        // Keep catalog records and their existing listing references on rollback.
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropIndex(['device_type']);
            $table->dropColumn('device_type');
        });
    }

    private function splitBrand(object $brand, string $type): void
    {
        $baseSlug = substr($brand->slug, 0, 220).'-'.$type;
        $slug = $baseSlug;
        $suffix = 2;

        while (DB::table('brands')->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $brandId = DB::table('brands')->insertGetId([
            ...array_diff_key((array) $brand, ['id' => true]),
            'slug' => $slug,
            'device_type' => $type,
        ]);

        foreach (DB::table('brand_listing_wizard_steps')->where('brand_id', $brand->id)->get() as $step) {
            DB::table('brand_listing_wizard_steps')->insert([
                ...array_diff_key((array) $step, ['id' => true]),
                'brand_id' => $brandId,
            ]);
        }

        $models = DB::table('phone_models')->where('brand_id', $brand->id)->where('device_type', $type);
        $seriesIds = (clone $models)->whereNotNull('phone_series_id')->distinct()->pluck('phone_series_id');

        DB::table('listings')->whereIn('phone_model_id', (clone $models)->select('id'))->update(['brand_id' => $brandId]);

        foreach (DB::table('phone_series')->whereIn('id', $seriesIds)->get() as $series) {
            $seriesId = DB::table('phone_series')->insertGetId([
                ...array_diff_key((array) $series, ['id' => true]),
                'brand_id' => $brandId,
            ]);

            (clone $models)->where('phone_series_id', $series->id)
                ->update(['brand_id' => $brandId, 'phone_series_id' => $seriesId]);
        }

        $models->update(['brand_id' => $brandId]);
    }
};
