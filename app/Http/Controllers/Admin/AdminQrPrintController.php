<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Qr\GenerateStationQr;
use App\Data\Admin\PlateData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Inertia\Inertia;
use Inertia\Response;

/**
 * See plan/phases/phase-08-admin-qr.md M8.5.
 */
class AdminQrPrintController extends Controller
{
    public function __invoke(Station $station, GenerateStationQr $generate): Response
    {
        $svg = $generate($station, 'svg');

        return Inertia::render('admin/stations/qr-print', [
            'plates' => [PlateData::from($station, $svg->getDataUri())],
            'brandOperator' => config('dossier.branding.operator'),
        ]);
    }
}
