<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Station;

/**
 * Drives the four module tiles on the Overview screen (Coordinates,
 * As-Built, Site Photos, 360° View) — whether each is enabled and, for the
 * media-backed ones, how many items it has. Photo/panorama/document counts
 * are always 0 until Phase 06/07 install spatie/laravel-medialibrary; the
 * tile itself is simply disabled until then, which is the correct state
 * for a station with genuinely no media yet.
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
            photoCount: 0,
            hasPanorama: false,
            documentCount: 0,
        );
    }
}
