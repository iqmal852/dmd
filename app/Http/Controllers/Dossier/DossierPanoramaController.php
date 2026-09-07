<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\PanoramaData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The 360° panorama viewer — Picture1.png panel 4's "360° View" module.
 * See plan/phases/phase-06-photos-360.md M6.5.
 */
class DossierPanoramaController extends Controller
{
    public function __invoke(Station $station): Response
    {
        $media = $station->getFirstMedia('panoramas');

        abort_if($media === null, HttpResponse::HTTP_NOT_FOUND, 'No 360° panorama available for this station.');

        return Inertia::render('dossier/panorama', [
            'stationPublicId' => $station->public_id,
            'stationCode' => $station->code,
            'panorama' => PanoramaData::from($media),
        ]);
    }
}
