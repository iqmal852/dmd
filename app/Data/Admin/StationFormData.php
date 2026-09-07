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
        public string $highway,
        public ?string $section,
        public string $km,
        public string $direction,
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
            highway: $station->highway,
            section: $station->section,
            km: (string) $station->km,
            direction: $station->direction->value,
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
            highway: '',
            section: null,
            km: '',
            direction: '',
            monumentType: '',
            installedAt: null,
            status: '',
            description: null,
            hasAccessPassword: false,
            isPublished: false,
        );
    }
}
