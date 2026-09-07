<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Station;

/**
 * One row on the admin Station index — plan/phases/phase-08-admin-qr.md
 * M8.2. Exact shape, never a raw model, per ADR-010.
 */
final readonly class StationListItemData
{
    public function __construct(
        public string $publicId,
        public string $code,
        public string $highway,
        public ?string $section,
        public string $km,
        public string $status,
        public string $statusColor,
        public bool $isPublished,
        public bool $hasCoordinates,
        public int $photoCount,
        public bool $hasPanorama,
        public int $documentCount,
    ) {}

    public static function from(Station $station): self
    {
        return new self(
            publicId: $station->public_id,
            code: $station->code,
            highway: $station->highway,
            section: $station->section,
            km: (string) $station->km,
            status: $station->status->label(),
            statusColor: $station->status->color(),
            isPublished: $station->is_published,
            hasCoordinates: $station->coordinateSet !== null,
            photoCount: $station->getMedia('photos')->count(),
            hasPanorama: $station->getFirstMedia('panoramas') !== null,
            documentCount: $station->getMedia('documents')
                ->filter(fn ($media) => $media->getCustomProperty('document_type') !== null)
                ->count(),
        );
    }
}
