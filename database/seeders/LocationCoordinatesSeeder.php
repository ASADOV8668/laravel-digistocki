<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;

class LocationCoordinatesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/iran-city-coordinates.json');
        if (! is_file($path)) {
            $this->command?->warn('مختصات استان‌ها و شهرها پیدا نشد؛ مکان‌یابی از داده‌های محلی عبور کرد.');

            return;
        }

        $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $provinces = Province::query()->get(['id', 'name'])->keyBy(fn (Province $province) => $this->normalize($province->name));
        $cities = City::query()->get(['id', 'name', 'province_id'])->groupBy(fn (City $city) => $this->normalize($city->name));
        $provinceCoordinates = [];

        foreach ($payload as $provinceData) {
            $province = $provinces->get($this->normalize((string) ($provinceData['province'] ?? '')));
            if (! $province || ! $this->validCoordinate($provinceData)) {
                continue;
            }

            $provinceCoordinates[$province->id] = [
                'latitude' => (float) $provinceData['latitude'],
                'longitude' => (float) $provinceData['longitude'],
            ];
            DB::table('provinces')->where('id', $province->id)->update($provinceCoordinates[$province->id]);

            foreach ((array) ($provinceData['cities'] ?? []) as $cityData) {
                if (! $this->validCoordinate($cityData)) {
                    continue;
                }

                $city = collect($cities->get($this->normalize((string) ($cityData['name'] ?? '')), []))
                    ->first(fn (City $candidate) => (int) $candidate->province_id === (int) $province->id);
                if (! $city) {
                    continue;
                }

                DB::table('cities')->where('id', $city->id)->update([
                    'latitude' => (float) $cityData['latitude'],
                    'longitude' => (float) $cityData['longitude'],
                ]);
            }
        }

        // Some package records are administrative aliases not present in the
        // public coordinate source. Use their province center as a safe,
        // deterministic fallback so every selectable city remains searchable.
        foreach ($provinceCoordinates as $provinceId => $coordinates) {
            DB::table('cities')
                ->where('province_id', $provinceId)
                ->whereNull('latitude')
                ->update($coordinates);
        }
    }

    private function validCoordinate(array $record): bool
    {
        return isset($record['latitude'], $record['longitude'])
            && is_numeric($record['latitude'])
            && is_numeric($record['longitude'])
            && (float) $record['latitude'] >= -90
            && (float) $record['latitude'] <= 90
            && (float) $record['longitude'] >= -180
            && (float) $record['longitude'] <= 180;
    }

    private function normalize(string $value): string
    {
        $value = str_replace(['ي', 'ى', 'ك', 'ة', 'ۀ', '‌'], ['ی', 'ی', 'ک', 'ه', 'ه', ' '], $value);

        return (string) preg_replace('/\s+/u', '', trim($value));
    }
}
