<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Sadegh19b\LaravelIranCities\Seeders\IranCitiesSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            IranCitiesSeeder::class,
            CatalogSeeder::class,
            DemoListingSeeder::class,
            SystemOptionsSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoAdminSeeder::class);
        }
    }
}
