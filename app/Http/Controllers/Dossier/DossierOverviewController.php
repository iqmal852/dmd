<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\StationSummaryData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\GeoFormatter;
use App\Services\StationCache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The screen a scanned QR code opens. See
 * plan/phases/phase-03-dossier-shell.md M3.5/M3.6 and
 * plan/phases/phase-09-hardening-release.md M9.2 for the caching.
 */
class DossierOverviewController extends Controller
{
    public function __invoke(Station $station, GeoFormatter $formatter): Response
    {
        $data = StationCache::remember($station, 'overview', function () use ($station, $formatter) {
            // 'media' is loaded once here so ModuleAvailability's
            // getMedia('photos') and getFirstMedia('panoramas') both read
            // the same cached Eloquent relation in-memory instead of
            // issuing a query each — see
            // plan/phases/phase-06-photos-360.md M6.1 and the query-count
            // budget in plan/phases/phase-03-dossier-shell.md's Test Gate.
            $station->loadMissing(['coordinateSet', 'specification', 'media']);

            return StationSummaryData::from($station, $formatter);
        });

        return Inertia::render('dossier/overview', [
            'station' => $data,
        ]);
    }
}
