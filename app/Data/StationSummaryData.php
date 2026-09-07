<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Station;
use App\Services\GeoFormatter;

/**
 * Exact shape of the `station` prop on the dossier Overview page. Deliberately
 * explicit and narrow — see plan/01-architecture.md ADR-010: public dossier
 * controllers must never pass a model or a ->toArray() to Inertia. Every
 * field here is display-ready; no formatting happens in TypeScript.
 */
final readonly class StationSummaryData
{
    public function __construct(
        public string $publicId,
        public string $code,
        public string $highway,
        public ?string $section,
        public string $km,
        public string $direction,
        public string $monumentType,
        public ?string $installedAt,
        public string $status,
        public string $statusColor,
        public ?QuickViewData $quickView,
        public ModuleAvailability $modules,
    ) {}

    public static function from(Station $station, GeoFormatter $formatter): self
    {
        return new self(
            publicId: $station->public_id,
            code: $station->code,
            highway: $station->highway,
            section: $station->section,
            km: $formatter->km($station->km),
            direction: $station->direction->label(),
            monumentType: $station->monument_type,
            installedAt: $formatter->installedDate($station->installed_at),
            status: $station->status->label(),
            statusColor: $station->status->color(),
            quickView: $station->coordinateSet !== null
                ? QuickViewData::from($station->coordinateSet, $formatter)
                : null,
            modules: ModuleAvailability::from($station),
        );
    }
}
