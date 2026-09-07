<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Specification;
use App\Services\GeoFormatter;

/**
 * The GNSS observation + accuracy (RMS) QC record — Picture1.png panel 2b.
 * Every field is nullable except qcStatus/qcStatusColor: real survey
 * records arrive incomplete, and the Specs & QC screen must render that.
 * See plan/phases/phase-04-coordinates-specs.md M4.1.
 */
final readonly class SpecificationData
{
    public function __construct(
        public ?string $observationMethod,
        public ?string $observationMinutes,
        public ?int $satelliteCount,
        public ?string $pdopMax,
        public ?string $elevationCutoff,
        public ?string $antennaType,
        public ?string $antennaHeight,
        public ?string $antennaReferencePoint,
        public ?string $horizontalRms,
        public ?string $verticalRms,
        public string $qcStatus,
        public string $qcStatusColor,
    ) {}

    public static function from(Specification $specification, GeoFormatter $formatter): self
    {
        return new self(
            observationMethod: $specification->observation_method,
            observationMinutes: $specification->observation_minutes !== null
                ? $formatter->minutes($specification->observation_minutes)
                : null,
            satelliteCount: $specification->satellite_count,
            pdopMax: $specification->pdop_max !== null
                ? $formatter->plainNumber($specification->pdop_max)
                : null,
            elevationCutoff: $specification->elevation_cutoff_deg !== null
                ? $formatter->degrees($specification->elevation_cutoff_deg)
                : null,
            antennaType: $specification->antenna_type,
            antennaHeight: $specification->antenna_height !== null
                ? $formatter->metres($specification->antenna_height)
                : null,
            antennaReferencePoint: $specification->antenna_reference_point,
            horizontalRms: $specification->horizontal_rms_mm !== null
                ? $formatter->tolerance($specification->horizontal_rms_mm)
                : null,
            verticalRms: $specification->vertical_rms_mm !== null
                ? $formatter->tolerance($specification->vertical_rms_mm)
                : null,
            qcStatus: $specification->qc_status->label(),
            qcStatusColor: $specification->qc_status->color(),
        );
    }
}
