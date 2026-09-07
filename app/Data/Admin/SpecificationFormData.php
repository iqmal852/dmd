<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Specification;

/**
 * Raw, editable values for the admin specification form. See
 * plan/phases/phase-08-admin-qr.md M8.6.
 */
final readonly class SpecificationFormData
{
    public function __construct(
        public ?string $observationMethod,
        public ?int $observationMinutes,
        public ?int $satelliteCount,
        public ?string $pdopMax,
        public ?int $elevationCutoffDeg,
        public ?string $antennaType,
        public ?string $antennaHeight,
        public ?string $antennaReferencePoint,
        public ?string $horizontalRmsMm,
        public ?string $verticalRmsMm,
        public string $qcStatus,
        public ?string $verifiedAt,
        public ?string $verifiedBy,
        public ?string $remarks,
    ) {}

    public static function from(Specification $specification): self
    {
        return new self(
            observationMethod: $specification->observation_method,
            observationMinutes: $specification->observation_minutes,
            satelliteCount: $specification->satellite_count,
            pdopMax: $specification->pdop_max,
            elevationCutoffDeg: $specification->elevation_cutoff_deg,
            antennaType: $specification->antenna_type,
            antennaHeight: $specification->antenna_height,
            antennaReferencePoint: $specification->antenna_reference_point,
            horizontalRmsMm: $specification->horizontal_rms_mm,
            verticalRmsMm: $specification->vertical_rms_mm,
            qcStatus: $specification->qc_status->value,
            verifiedAt: $specification->verified_at?->toDateString(),
            verifiedBy: $specification->verified_by,
            remarks: $specification->remarks,
        );
    }
}
