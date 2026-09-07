<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\DocumentType;
use App\Models\Station;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One row on the Files screen — Picture1.png panel 3. `id` and every URL
 * are built from the media row's UUID, never its numeric primary key, and
 * every URL routes through this app's own gated controllers rather than a
 * medialibrary-generated disk URL — as-built drawings are never publicly
 * reachable. See plan/phases/phase-07-asbuilt-files.md M7.1/M7.2.
 */
final readonly class DocumentData
{
    /**
     * Genuinely browser-renderable image mimes. Deliberately not a
     * `str_starts_with($mime, 'image/')` check: Symfony's mime guesser
     * correctly identifies a DWG file's binary signature as
     * `image/vnd.dwg` (that is its real registered mime type), which no
     * browser can render despite the `image/` prefix.
     */
    private const array RENDERABLE_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];

    public function __construct(
        public string $id,
        public string $type,
        public string $typeLabel,
        public string $title,
        public ?string $revision,
        public string $extension,
        public string $size,
        public bool $isPrimary,
        public ?string $previewUrl,
        public string $previewKind,
        public string $downloadUrl,
    ) {}

    public static function from(Media $media, Station $station): self
    {
        $type = DocumentType::from((string) $media->getCustomProperty('document_type'));
        $previewMedia = self::resolvePreviewMedia($media, $station);

        return new self(
            id: (string) $media->uuid,
            type: $type->value,
            typeLabel: $type->label(),
            title: (string) ($media->getCustomProperty('title') ?: $type->label()),
            revision: $media->getCustomProperty('revision'),
            extension: strtoupper((string) $media->extension),
            size: (string) $media->human_readable_size,
            isPrimary: (bool) $media->getCustomProperty('is_primary', false),
            previewUrl: $previewMedia !== null
                ? route('dossier.files.preview', ['station' => $station, 'media' => $previewMedia->uuid])
                : null,
            previewKind: self::previewKindFor($previewMedia),
            downloadUrl: route('dossier.files.download', ['station' => $station, 'media' => $media->uuid]),
        );
    }

    /**
     * A PDF or image original is its own preview. A DWG/DXF (or anything
     * else a browser can't render) falls back to the sibling media row
     * named by its `preview_media_id` custom property, if one was
     * attached — otherwise there is nothing to show inline at all.
     */
    private static function resolvePreviewMedia(Media $media, Station $station): ?Media
    {
        if (self::isDirectlyRenderable($media)) {
            return $media;
        }

        $previewMediaId = $media->getCustomProperty('preview_media_id');

        if ($previewMediaId === null) {
            return null;
        }

        return $station->getMedia('documents')
            ->firstWhere('id', (int) $previewMediaId);
    }

    private static function isDirectlyRenderable(Media $media): bool
    {
        return self::isRenderableMime($media->mime_type);
    }

    public static function isRenderableMime(string $mime): bool
    {
        return $mime === 'application/pdf' || in_array($mime, self::RENDERABLE_IMAGE_MIMES, true);
    }

    private static function previewKindFor(?Media $previewMedia): string
    {
        return match (true) {
            $previewMedia === null => 'none',
            $previewMedia->mime_type === 'application/pdf' => 'pdf',
            in_array($previewMedia->mime_type, self::RENDERABLE_IMAGE_MIMES, true) => 'image',
            default => 'none',
        };
    }
}
