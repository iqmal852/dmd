<?php

declare(strict_types=1);

namespace App\Data\Admin;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One photo row in the admin media manager — plan/phases/phase-08-admin-qr.md
 * M8.7. `id` is the media UUID, never the numeric primary key.
 */
final readonly class PhotoFormData
{
    public function __construct(
        public string $id,
        public ?int $bearing,
        public ?string $label,
        public string $thumbUrl,
    ) {}

    public static function from(Media $media): self
    {
        return new self(
            id: (string) $media->uuid,
            bearing: $media->getCustomProperty('bearing'),
            label: $media->getCustomProperty('label'),
            thumbUrl: $media->getFullUrl('thumb'),
        );
    }
}
