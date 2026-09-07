<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\CoordinateSet;
use App\Services\GeoFormatter;

/**
 * The compact three-line coordinate summary on the Overview screen —
 * Picture1.png panel 1's "QUICK VIEW" card. The full per-system breakdown
 * (Specs & QC included) lives on the Coordinates screen, built in Phase 04.
 */
final readonly class QuickViewData
{
    public function __construct(
        public string $latitude,
        public string $longitude,
        public string $ellipsoidalHeight,
        public string $easting,
        public string $northing,
        public string $orthometricHeight,
    ) {}

    public static function from(CoordinateSet $coordinateSet, GeoFormatter $formatter): self
    {
        return new self(
            latitude: $formatter->latitude($coordinateSet->latitude),
            longitude: $formatter->longitude($coordinateSet->longitude),
            ellipsoidalHeight: $formatter->metres($coordinateSet->ellipsoidal_height),
            easting: $formatter->metresGrouped($coordinateSet->easting),
            northing: $formatter->metresGrouped($coordinateSet->northing),
            orthometricHeight: $formatter->metres($coordinateSet->orthometric_height),
        );
    }
}
