<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * plan/phases/phase-08-admin-qr.md M8.7.
 */
final readonly class PanoramaFormData
{
    public function __construct(
        public string $id,
        public string $url,
        public float $initialYaw,
        public float $initialPitch,
        public int $hfov,
    ) {}

    public static function from(Media $media): self
    {
        return new self(
            id: (string) $media->uuid,
            url: $media->getFullUrl(),
            initialYaw: (float) ($media->getCustomProperty('initial_yaw') ?: 0),
            initialPitch: (float) ($media->getCustomProperty('initial_pitch') ?: 0),
            hfov: (int) ($media->getCustomProperty('hfov') ?: 100),
        );
    }
}
