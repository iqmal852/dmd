<?php

declare(strict_types=1);

namespace App\Actions\Qr;

/**
 * A duck-typed stand-in for Endroid's `ResultInterface`, exposing only
 * the three methods any caller actually uses (getString/getMimeType/
 * getDataUri — checked, nothing here uses getMatrix() or saveToFile()).
 *
 * Deliberately never itself the thing that gets cached — see
 * GenerateStationQr, which caches a plain array and constructs this
 * fresh from it on every call. PngWriter's real result wraps a live
 * GdImage (unserializable at all), and even once reduced to plain
 * strings, config('cache.serializable_classes') defaults to `false` on
 * the `database` cache store, which makes PHP's unserialize() refuse
 * *any* object class, not just GdImage-bearing ones — so this class
 * exists for callers' convenience only, not as cache payload.
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
