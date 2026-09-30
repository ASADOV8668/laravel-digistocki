<?php

namespace Tests\Feature;

use App\Models\Brand;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredNineteenCatalogDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_legacy_apple_brand_name_is_migrated_safely(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();
        $brand->update(['name' => 'آیفون']);

        $migration = require base_path('database/migrations/2026_09_30_001200_correct_apple_persian_brand_name.php');
        $migration->up();

        $this->assertSame('اپل', $brand->refresh()->name);

        $migration->down();

        $this->assertSame('آیفون', $brand->refresh()->name);
    }
}
