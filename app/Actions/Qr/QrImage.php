<?php

declare(strict_types=1);

namespace App\Actions\Qr;

/**
 * A plain, fully-serializable stand-in for Endroid's `ResultInterface`.
 *
 * `GenerateStationQr` caches its output (Cache::remember, database
 * driver), and PngWriter's result wraps a live `GdImage` — PHP explicitly
 * refuses to serialize that ("Serialization of 'GdImage' is not
 * allowed"), which blew up on every cache write. This holds only the
 * three primitives every caller actually needs (getString/getMimeType/
 * getDataUri — checked, nothing here uses getMatrix() or saveToFile()),
 * computed once from the real result before it ever reaches the cache.
 */
final readonly class QrImage
{
    public function __construct(
        private string $content,
        private string $mimeType,
        private string $dataUri,
    ) {}

    public function getString(): string
    {
        return $this->content;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getDataUri(): string
    {
        return $this->dataUri;
    }
}
