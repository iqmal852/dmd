<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The single 360° panorama for a station — Picture1.png panel 4's
 * "360° PANORAMA (Full Surrounding)". See
 * plan/phases/phase-06-photos-360.md M6.5.
 */
final readonly class PanoramaData
{
    public function __construct(
        public string $url,
        public float $initialYaw,
        public float $initialPitch,
        public int $hfov,
    ) {}

    public static function from(Media $media): self
    {
        return new self(
            url: $media->getFullUrl(),
            initialYaw: (float) ($media->getCustomProperty('initial_yaw') ?: 0),
            initialPitch: (float) ($media->getCustomProperty('initial_pitch') ?: 0),
            hfov: (int) ($media->getCustomProperty('hfov') ?: 100),
        );
    }
}
