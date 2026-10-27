<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Station;

/**
 * Drives the module tiles on the Overview screen (Coordinates,
 * As-Built, Site Photos) — whether each is enabled and, for the
 * media-backed ones, how many items it has.
 */
final readonly class ModuleAvailability
{
    public function __construct(
        public bool $hasCoordinates,
        public bool $hasSpecification,
        public int $photoCount,
        public int $documentCount,
    ) {}

    public static function from(Station $station): self
    {
        return new self(
            hasCoordinates: $station->coordinateSet !== null,
            hasSpecification: $station->specification !== null,
            photoCount: $station->getMedia('photos')->count(),
            // Excludes preview-only companion rows for a DWG/DXF original
            // (see plan/phases/phase-07-asbuilt-files.md M7.1) — those
            // aren't documents in their own right.
            documentCount: $station->getMedia('documents')
                ->filter(fn ($media) => $media->getCustomProperty('document_type') !== null)
                ->count(),
        );
    }
}
