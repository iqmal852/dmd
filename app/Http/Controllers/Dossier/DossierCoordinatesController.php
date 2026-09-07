<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\CoordinateSetData;
use App\Data\SpecificationData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\GeoFormatter;
use App\Services\StationCache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Picture1.png panels 2a (Coordinates) and 2b (Specs & QC), on one scrolling
 * page — see plan/phases/phase-04-coordinates-specs.md M4.2/M4.3 and
 * plan/phases/phase-09-hardening-release.md M9.2 for the caching.
 */
class DossierCoordinatesController extends Controller
{
    public function __invoke(Station $station, GeoFormatter $formatter): Response
    {
        $data = StationCache::remember($station, 'coordinates', function () use ($station, $formatter) {
            $station->loadMissing(['coordinateSet', 'specification']);

            return [
                'coordinateSet' => $station->coordinateSet !== null
                    ? CoordinateSetData::from($station->coordinateSet, $formatter)
                    : null,
                'specification' => $station->specification !== null
                    ? SpecificationData::from($station->specification, $formatter)
                    : null,
            ];
        });

        return Inertia::render('dossier/coordinates', [
            'stationCode' => $station->code,
            'stationPublicId' => $station->public_id,
            ...$data,
        ]);
    }
}
