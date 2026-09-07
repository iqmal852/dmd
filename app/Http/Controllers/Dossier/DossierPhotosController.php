<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\PhotoData;
use App\Enums\PhotoType;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Picture1.png panel 4 — see plan/phases/phase-06-photos-360.md M6.3.
 */
class DossierPhotosController extends Controller
{
    public function __invoke(Station $station): Response
    {
        $photos = $station->getMedia('photos')
            ->sortBy(fn ($media) => $media->getCustomProperty('photo_type') === PhotoType::EyeLevel->value ? 0 : 1)
            ->values()
            ->map(fn ($media) => PhotoData::from($media))
            ->all();

        $panoramaMedia = $station->getFirstMedia('panoramas');

        return Inertia::render('dossier/photos', [
            'stationPublicId' => $station->public_id,
            'stationCode' => $station->code,
            'photos' => $photos,
            'hasPanorama' => $panoramaMedia !== null,
        ]);
    }
}
