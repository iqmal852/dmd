<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Station;

/**
 * Drives the four module tiles on the Overview screen (Coordinates,
 * As-Built, Site Photos, 360° View) — whether each is enabled and, for the
 * media-backed ones, how many items it has. `documentCount` is always 0
 * until Phase 07 wires up the `documents` media collection; the As-Built
 * tile is simply disabled until then, which is the correct state for a
 * station with genuinely no as-built drawing uploaded yet.
 */
final readonly class ModuleAvailability
{
    public function __construct(
        public bool $hasCoordinates,
        public bool $hasSpecification,
        public int $photoCount,
        public bool $hasPanorama,
        public int $documentCount,
    ) {}

    public static function from(Station $station): self
    {
        return new self(
            hasCoordinates: $station->coordinateSet !== null,
            hasSpecification: $station->specification !== null,
            photoCount: $station->getMedia('photos')->count(),
            hasPanorama: $station->getFirstMedia('panoramas') !== null,
            documentCount: 0,
        );
    }
}
