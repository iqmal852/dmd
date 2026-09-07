<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Station;
use App\Services\GeoFormatter;
use App\Services\SlippyMapMath;

/**
 * The non-interactive map preview on the Overview screen — a single
 * satellite tile with the pin CSS-positioned over it, zero JS, no Leaflet
 * (see plan/phases/phase-05-location-map.md M5.4, approach 1: "static tile
 * composite"). The pin's position is expressed as a percentage within the
 * tile so the frontend can place it with plain CSS.
 */
final readonly class MapPreviewData
{
    private const int ZOOM = 15;

    public function __construct(
        public string $tileUrl,
        public string $attribution,
        public float $pinLeftPercent,
        public float $pinTopPercent,
        public string $kmBefore,
        public string $kmAfter,
    ) {}

    public static function from(Station $station, GeoFormatter $formatter): ?self
    {
        if ($station->coordinateSet === null) {
            return null;
        }

        $tile = SlippyMapMath::tileForPoint(
            (float) $station->coordinateSet->latitude,
            (float) $station->coordinateSet->longitude,
            self::ZOOM,
        );

        $tileUrl = strtr((string) config('dossier.map.satellite_url'), [
            '{z}' => (string) self::ZOOM,
            '{x}' => (string) $tile['x'],
            '{y}' => (string) $tile['y'],
        ]);

        $km = (float) $station->km;

        return new self(
            tileUrl: $tileUrl,
            attribution: (string) config('dossier.map.satellite_attr'),
            pinLeftPercent: round($tile['fractionX'] * 100, 2),
            pinTopPercent: round($tile['fractionY'] * 100, 2),
            kmBefore: $formatter->km(number_format(max(0, $km - 0.2), 3, '.', '')),
            kmAfter: $formatter->km(number_format($km + 0.2, 3, '.', '')),
        );
    }
}
