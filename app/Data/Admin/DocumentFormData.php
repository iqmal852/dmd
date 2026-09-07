<?php

declare(strict_types=1);

namespace App\Data\Admin;

use App\Data\DocumentData;
use App\Enums\DocumentType;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * plan/phases/phase-08-admin-qr.md M8.7.
 */
final readonly class DocumentFormData
{
    public function __construct(
        public string $id,
        public string $documentType,
        public string $title,
        public ?string $revision,
        public bool $isPrimary,
        public string $extension,
        public string $size,
        public bool $hasPreview,
    ) {}

    public static function from(Media $media): self
    {
        $type = DocumentType::from((string) $media->getCustomProperty('document_type'));

        return new self(
            id: (string) $media->uuid,
            documentType: $type->value,
            title: (string) ($media->getCustomProperty('title') ?: $type->label()),
            revision: $media->getCustomProperty('revision'),
            isPrimary: (bool) $media->getCustomProperty('is_primary', false),
            extension: strtoupper((string) $media->extension),
            size: (string) $media->human_readable_size,
            hasPreview: $media->getCustomProperty('preview_media_id') !== null
                || DocumentData::isRenderableMime($media->mime_type),
        );
    }
}
