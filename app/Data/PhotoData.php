<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One Site Photos carousel item — Picture1.png panel 4. `id` is the
 * media row's UUID, not its numeric primary key, so the prop never
 * reveals storage layout or row counts. Photos carry a free-text label
 * only — no fixed type/category and no cap on how many a station may
 * have, per explicit user request. See
 * plan/phases/phase-06-photos-360.md M6.2.
 */
final readonly class PhotoData
{
    public function __construct(
        public string $id,
        public string $label,
        public ?int $bearing,
        public ?string $capturedAt,
        public string $thumbUrl,
        public string $previewUrl,
        public string $srcset,
        public string $placeholder,
        public int $width,
        public int $height,
    ) {}

    public static function from(Media $media): self
    {
        $thumbUrl = $media->getFullUrl('thumb');
        $previewUrl = $media->getFullUrl('preview');

        return new self(
            id: (string) $media->uuid,
            label: (string) ($media->getCustomProperty('label') ?: 'Site Photo'),
            bearing: $media->getCustomProperty('bearing'),
            capturedAt: $media->getCustomProperty('captured_at'),
            thumbUrl: $thumbUrl,
            previewUrl: $previewUrl,
            srcset: "{$thumbUrl} 320w, {$previewUrl} 1200w",
            placeholder: self::placeholderDataUri($media),
            width: (int) ($media->getCustomProperty('width') ?: 1200),
            height: (int) ($media->getCustomProperty('height') ?: 800),
        );
    }

    private static function placeholderDataUri(Media $media): string
    {
        try {
            $disk = Storage::disk($media->conversions_disk ?: $media->disk);
            $contents = $disk->get($media->getPathRelativeToRoot('placeholder'));

            if ($contents === null) {
                return '';
            }

            return 'data:image/webp;base64,'.base64_encode($contents);
        } catch (\Throwable) {
            // A missing/not-yet-generated conversion falls back to the
            // original image rather than a broken placeholder — see
            // plan/phases/phase-06-photos-360.md M6.6.
            return '';
        }
    }
}
