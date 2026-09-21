<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Station;

/**
 * Raw, editable values for the station create/edit form — deliberately
 * unformatted (unlike StationSummaryData, which is display-ready for the
 * read-only public dossier). `hasAccessPassword` tells the form whether to
 * show "this station has its own password"; the hash itself never leaves
 * the server. See plan/phases/phase-08-admin-qr.md M8.3.
 */
final readonly class StationFormData
{
    public function __construct(
        public ?string $publicId,
        public string $code,
        public ?string $gcpReference,
        public string $highway,
        public ?string $section,
        public ?string $location,
        public string $km,
        public string $direction,
        public ?string $facilityType,
        public string $monumentType,
        public ?string $installedAt,
        public string $status,
        public ?string $description,
        public bool $hasAccessPassword,
        public bool $isPublished,
    ) {}

    public static function from(Station $station): self
    {
        return new self(
            publicId: $station->public_id,
            code: $station->code,
            gcpReference: $station->gcp_reference,
            highway: $station->highway,
            section: $station->section,
            location: $station->location,
            km: (string) $station->km,
            direction: $station->direction->value,
            facilityType: $station->facility_type,
            monumentType: $station->monument_type,
            installedAt: $station->installed_at?->toDateString(),
            status: $station->status->value,
            description: $station->description,
            hasAccessPassword: $station->access_password !== null,
            isPublished: $station->is_published,
        );
    }

    public static function empty(): self
    {
        return new self(
            publicId: null,
            code: '',
            gcpReference: null,
            highway: '',
            section: null,
            location: null,
            km: '',
            direction: '',
            facilityType: null,
            monumentType: '',
            installedAt: null,
            status: '',
            description: null,
            hasAccessPassword: false,
            isPublished: false,
        );
    }
}
