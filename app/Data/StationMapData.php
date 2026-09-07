<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Station;
use App\Services\GeoFormatter;

/**
 * The Location Map screen — Picture1.png panel 2c. Latitude/longitude are
 * plain numbers here (Leaflet needs numeric input), unlike every other
 * dossier DTO which pre-formats everything to a display string. Every
 * other field is still pre-formatted for the detail card. See
 * plan/phases/phase-05-location-map.md M5.2/M5.3.
 */
final readonly class StationMapData
{
    public function __construct(
        public string $code,
        public float $latitude,
        public float $longitude,
        public string $highway,
        public string $km,
        public string $direction,
        public ?string $section,
        public string $monumentType,
        public ?string $installedAt,
    ) {}

    public static function from(Station $station, GeoFormatter $formatter): ?self
    {
        if ($station->coordinateSet === null) {
            return null;
        }

        return new self(
            code: $station->code,
            latitude: (float) $station->coordinateSet->latitude,
            longitude: (float) $station->coordinateSet->longitude,
            highway: $station->highway,
            km: $formatter->km($station->km),
            direction: $station->direction->label(),
            section: $station->section,
            monumentType: $station->monument_type,
            installedAt: $formatter->installedDate($station->installed_at),
        );
    }
}
