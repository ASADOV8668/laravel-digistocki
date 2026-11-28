<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('seller_stores', 'legacy_slug')) {
            Schema::table('seller_stores', function (Blueprint $table): void {
                $table->string('legacy_slug', 80)->nullable()->unique();
            });
        }

        DB::transaction(function (): void {
            DB::table('seller_stores')->where('slug', 'like', '%-%')->chunkById(200, function ($stores): void {
                foreach ($stores as $store) {
                    $base = str_replace('-', '_', $store->slug);
                    $slug = $base;
                    for ($number = 2; DB::table('seller_stores')->where('slug', $slug)->exists(); $number++) {
                        $suffix = '_'.$number;
                        $slug = rtrim(substr($base, 0, 80 - strlen($suffix)), '_.').$suffix;
                    }

                    DB::table('seller_stores')->where('id', $store->id)->update([
                        'slug' => $slug,
                        'legacy_slug' => $store->slug,
                    ]);
                }
            });
        });
    }

    public function down(): void
    {
        DB::table('seller_stores')->whereNotNull('legacy_slug')->update(['slug' => DB::raw('legacy_slug')]);
        Schema::table('seller_stores', function (Blueprint $table): void {
            $table->dropUnique(['legacy_slug']);
            $table->dropColumn('legacy_slug');
        });
    }
};
