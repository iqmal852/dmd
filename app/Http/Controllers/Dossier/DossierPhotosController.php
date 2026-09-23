<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\PhotoData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\StationCache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Picture1.png panel 4 — see plan/phases/phase-06-photos-360.md M6.3 and
 * plan/phases/phase-09-hardening-release.md M9.2 for the caching. Photos
 * render in upload order (Spatie's own `order_column`) — there is no
 * type/category to sort by.
 */
class DossierPhotosController extends Controller
{
    public function __invoke(Station $station): Response
    {
        $data = StationCache::remember($station, 'photos', function () use ($station) {
            $photos = $station->getMedia('photos')
                ->map(fn ($media) => PhotoData::from($media))
                ->values()
                ->all();

            return [
                'photos' => $photos,
                'hasPanorama' => $station->getFirstMedia('panoramas') !== null,
            ];
        });

        return Inertia::render('dossier/photos', [
            'stationPublicId' => $station->public_id,
            'stationCode' => $station->code,
            ...$data,
        ]);
    }
}
