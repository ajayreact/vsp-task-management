<?php

namespace App\Modules\Attendance\Support;

/**
 * Haversine distance between two WGS-84 coordinates.
 */
final class GpsGeofence
{
    private const EARTH_RADIUS_METERS = 6_371_000;

    public static function distanceInMeters(
        float $latitudeFrom,
        float $longitudeFrom,
        float $latitudeTo,
        float $longitudeTo,
    ): float {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    public static function isWithinRadius(
        float $latitude,
        float $longitude,
        float $officeLatitude,
        float $officeLongitude,
        int $allowedRadiusMeters,
    ): bool {
        return self::distanceInMeters(
            $latitude,
            $longitude,
            $officeLatitude,
            $officeLongitude,
        ) <= $allowedRadiusMeters;
    }
}
