<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\StationMapData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\GeoFormatter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Picture1.png panel 2c — see plan/phases/phase-05-location-map.md M5.2/M5.3.
 */
class DossierMapController extends Controller
{
    public function __invoke(Station $station, GeoFormatter $formatter): Response
    {
        $station->loadMissing('coordinateSet');

        return Inertia::render('dossier/map', [
            'stationPublicId' => $station->public_id,
            'stationCode' => $station->code,
            'station' => StationMapData::from($station, $formatter),
            'map' => [
                'satelliteUrl' => config('dossier.map.satellite_url'),
                'satelliteAttribution' => config('dossier.map.satellite_attr'),
                'streetUrl' => config('dossier.map.street_url'),
                'streetAttribution' => config('dossier.map.street_attr'),
                'defaultZoom' => config('dossier.map.default_zoom'),
                'maxZoom' => config('dossier.map.max_zoom'),
            ],
        ]);
    }
}
