<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\StationSummaryData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\GeoFormatter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The screen a scanned QR code opens. See
 * plan/phases/phase-03-dossier-shell.md M3.5/M3.6.
 */
class DossierOverviewController extends Controller
{
    public function __invoke(Station $station, GeoFormatter $formatter): Response
    {
        $station->loadMissing(['coordinateSet', 'specification']);

        return Inertia::render('dossier/overview', [
            'station' => StationSummaryData::from($station, $formatter),
        ]);
    }
}
