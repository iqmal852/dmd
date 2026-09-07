<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\CoordinateSet;
use App\Services\GeoFormatter;

/**
 * The full per-system coordinate record — Picture1.png panel 2a. Every
 * field has a display form (with unit and separators) and, for lat/lon, a
 * raw bare-number form for copy-to-clipboard: a surveyor pasting into
 * survey software must not receive a degree sign or a thousands
 * separator. See plan/phases/phase-04-coordinates-specs.md M4.1.
 */
final readonly class CoordinateSetData
{
    public function __construct(
        public string $latitude,
        public string $longitude,
        public string $ellipsoidalHeight,
        public string $easting,
        public string $northing,
        public ?string $zone,
        public string $orthometricHeight,
        public string $geoidModel,
        public ?string $epoch,
        public string $latitudeRaw,
        public string $longitudeRaw,
    ) {}

    public static function from(CoordinateSet $coordinateSet, GeoFormatter $formatter): self
    {
        return new self(
            latitude: $formatter->latitude($coordinateSet->latitude),
            longitude: $formatter->longitude($coordinateSet->longitude),
            ellipsoidalHeight: $formatter->metres($coordinateSet->ellipsoidal_height),
            easting: $formatter->metresGrouped($coordinateSet->easting),
            northing: $formatter->metresGrouped($coordinateSet->northing),
            zone: $coordinateSet->zone,
            orthometricHeight: $formatter->metres($coordinateSet->orthometric_height),
            geoidModel: $coordinateSet->geoid_model,
            epoch: $coordinateSet->epoch,
            latitudeRaw: $coordinateSet->latitude,
            longitudeRaw: $coordinateSet->longitude,
        );
    }
}
