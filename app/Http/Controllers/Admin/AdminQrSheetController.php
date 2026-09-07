<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Qr\GenerateStationQr;
use App\Data\Admin\PlateData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * All stations, or a filtered subset via the same query-string filters as
 * the station index. See plan/phases/phase-08-admin-qr.md M8.5.
 */
class AdminQrSheetController extends Controller
{
    public function __invoke(Request $request, GenerateStationQr $generate): Response
    {
        $stations = Station::query()
            ->when($request->filled('highway'), fn ($query) => $query->where('highway', $request->query('highway')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderBy('km')
            ->get();

        return Inertia::render('admin/stations/qr-print', [
            'plates' => $stations
                ->map(fn (Station $station) => PlateData::from($station, $generate($station, 'svg')->getDataUri()))
                ->values(),
            'brandOperator' => config('dossier.branding.operator'),
        ]);
    }
}
