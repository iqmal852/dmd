<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Station;

/**
 * The single source of the URL encoded into every QR code — deliberately
 * built from `config('dossier.base_url')`, never Laravel's `route()`
 * helper or the current request's host. A QR plate bolted to a highway
 * monument is effectively permanent; the domain it points at must be
 * pinned independently of whatever host happens to serve a given request
 * (staging, a preview deploy, an internal load balancer). See
 * plan/04-env-configuration.md §5 and
 * plan/phases/phase-08-admin-qr.md M8.4 Test Gate #5.
 */
final readonly class QrUrlBuilder
{
    public function forStation(Station $station): string
    {
        // config/dossier.php already rtrim()s/trim()s these at parse time,
        // but this builder never relies solely on that — a slash here is
        // a permanently wrong printed QR plate, so it's trimmed again at
        // the point of use regardless of what the config value looks like.
        $base = rtrim((string) config('dossier.base_url'), '/');
        $prefix = trim((string) config('dossier.route_prefix'), '/');

        return "{$base}/{$prefix}/{$station->public_id}";
    }
}
