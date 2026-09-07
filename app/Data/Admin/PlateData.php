<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Models\Station;

/**
 * One physical QR plate — plan/phases/phase-08-admin-qr.md M8.5. The QR is
 * an SVG data URI, not PNG: vector art has no DPI ceiling, which matters
 * for a plate printed and engraved at real-world millimetre dimensions.
 */
final readonly class PlateData
{
    public function __construct(
        public string $publicId,
        public string $code,
        public string $highway,
        public string $qrDataUri,
    ) {}

    public static function from(Station $station, string $qrDataUri): self
    {
        return new self(
            publicId: $station->public_id,
            code: $station->code,
            highway: $station->highway,
            qrDataUri: $qrDataUri,
        );
    }
}
