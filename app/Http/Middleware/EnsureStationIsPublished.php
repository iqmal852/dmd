<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Station;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `{station}` resolves by `public_id` via Station::getRouteKeyName() alone
 * (default implicit binding — no custom Route::bind() involved), so this
 * middleware is the only thing enforcing "unpublished or soft-deleted
 * 404s" on the public dossier. It is deliberately scoped to the dossier
 * route group only, not global: an admin editing a station must be able
 * to reach it before it's published — that's the whole point of the
 * "published" toggle. See plan/phases/phase-08-admin-qr.md, which
 * surfaced the bug where a single global `Route::bind('station', ...)`
 * (Phase 03) silently made every admin `{station}` route 404 for any
 * not-yet-published station too.
 */
class EnsureStationIsPublished
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Station|null $station */
        $station = $request->route('station');

        abort_if($station === null || ! $station->is_published, 404);

        return $next($request);
    }
}
