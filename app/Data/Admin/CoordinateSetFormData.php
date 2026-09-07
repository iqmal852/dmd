<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\CoordinateSet;

/**
 * Raw, editable values for the admin coordinates form — unlike
 * CoordinateSetData, which is display-formatted (units, thousands
 * separators) for the read-only public dossier. See
 * plan/phases/phase-08-admin-qr.md M8.6.
 */
final readonly class CoordinateSetFormData
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
        public ?string $computedAt,
    ) {}

    public static function from(CoordinateSet $coordinateSet): self
    {
        return new self(
            latitude: $coordinateSet->latitude,
            longitude: $coordinateSet->longitude,
            ellipsoidalHeight: $coordinateSet->ellipsoidal_height,
            easting: $coordinateSet->easting,
            northing: $coordinateSet->northing,
            zone: $coordinateSet->zone,
            orthometricHeight: $coordinateSet->orthometric_height,
            geoidModel: $coordinateSet->geoid_model,
            epoch: $coordinateSet->epoch,
            computedAt: $coordinateSet->computed_at?->toDateString(),
        );
    }
}
