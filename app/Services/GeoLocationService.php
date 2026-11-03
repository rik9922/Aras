<?php

namespace App\Services;

class GeoLocationService
{
    /**
     * Haversine distance between two coordinates in meters.
     */
    public function distanceInMeters(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2
    ): float {
        $earthRadiusMeters = 6371000;

        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2))
            * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusMeters * $c, 2);
    }

    public function isWithinRadius(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2,
        ?int $radiusMeters = null
    ): bool {
        $radiusMeters ??= (int) config('attendance.check_in_radius_meters', 50);

        return $this->distanceInMeters($latitude1, $longitude1, $latitude2, $longitude2) <= $radiusMeters;
    }
}
