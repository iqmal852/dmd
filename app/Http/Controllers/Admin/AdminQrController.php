<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Qr\GenerateStationQr;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\QrUrlBuilder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * See plan/phases/phase-08-admin-qr.md M8.4.
 */
class AdminQrController extends Controller
{
    public function __invoke(Station $station, GenerateStationQr $generate, QrUrlBuilder $urlBuilder): Response
    {
        $png = $generate($station, 'png');

        return Inertia::render('admin/stations/qr', [
            'stationPublicId' => $station->public_id,
            'stationCode' => $station->code,
            'encodedUrl' => $urlBuilder->forStation($station),
            'previewDataUri' => $png->getDataUri(),
        ]);
    }
}
