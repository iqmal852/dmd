<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Station;
use Illuminate\Foundation\Events\Dispatchable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Fired on every successful as-built document download, whether or not
 * logging ends up persisting anything — see
 * plan/phases/phase-07-asbuilt-files.md M7.5. Carries the raw IP address;
 * only the queued listener ever turns it into a hash, and only if it
 * decides to write a row at all.
 */
final readonly class DocumentDownloaded
{
    use Dispatchable;

    public function __construct(
        public Station $station,
        public Media $media,
        public string $ip,
        public ?string $userAgent,
    ) {}
}
