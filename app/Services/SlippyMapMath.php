<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Standard Web Mercator "slippy map" tile math (the same scheme Leaflet,
 * OSM, and Esri all use), so the Overview screen's static preview image
 * (plan/phases/phase-05-location-map.md M5.4) can find the exact tile a
 * coordinate falls in — and where within that tile — without loading
 * Leaflet at all.
 *
 * @see https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames
 */
final class SlippyMapMath
{
    /**
     * @return array{x: int, y: int, fractionX: float, fractionY: float}
     */
    public static function tileForPoint(float $latitude, float $longitude, int $zoom): array
    {
        $tileCount = 2 ** $zoom;

        $xFloat = ($longitude + 180) / 360 * $tileCount;

        $latitudeRadians = deg2rad($latitude);
        $yFloat = (1 - log(tan($latitudeRadians) + 1 / cos($latitudeRadians)) / M_PI) / 2 * $tileCount;

        $x = (int) floor($xFloat);
        $y = (int) floor($yFloat);

        return [
            'x' => $x,
            'y' => $y,
            'fractionX' => $xFloat - $x,
            'fractionY' => $yFloat - $y,
        ];
    }
}
