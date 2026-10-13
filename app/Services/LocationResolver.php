<?php

namespace App\Services;

use Sadegh19b\LaravelIranCities\Models\City;
use Sadegh19b\LaravelIranCities\Models\Province;

class LocationResolver
{
    /** @return array{city_id:int, province_id:int, city_name:string, province_name:string, distance_km:float}|null */
    public function nearest(float $latitude, float $longitude): ?array
    {
        $city = $this->nearestRecord(
            City::query()->whereNotNull('latitude')->whereNotNull('longitude')->get(['id', 'name', 'province_id', 'latitude', 'longitude']),
            $latitude,
            $longitude,
        );
        if (! $city) {
            return null;
        }

        $province = Province::query()->find($city->province_id);
        if (! $province) {
            return null;
        }

        return [
            'city_id' => (int) $city->id,
            'province_id' => (int) $province->id,
            'city_name' => (string) $city->name,
            'province_name' => (string) $province->name,
            'distance_km' => round($this->distanceKm($latitude, $longitude, (float) $city->latitude, (float) $city->longitude), 2),
        ];
    }

    private function nearestRecord($records, float $latitude, float $longitude): mixed
    {
        return $records->sortBy(fn ($record): float => $this->distanceKm($latitude, $longitude, (float) $record->latitude, (float) $record->longitude))->first();
    }

    private function distanceKm(float $latitude, float $longitude, float $targetLatitude, float $targetLongitude): float
    {
        $latitudeRadians = deg2rad($latitude);
        $targetLatitudeRadians = deg2rad($targetLatitude);
        $deltaLongitude = deg2rad($targetLongitude - $longitude);
        $angle = sin($latitudeRadians) * sin($targetLatitudeRadians)
            + cos($latitudeRadians) * cos($targetLatitudeRadians) * cos($deltaLongitude);

        return 6371 * acos(max(-1, min(1, $angle)));
    }
}
