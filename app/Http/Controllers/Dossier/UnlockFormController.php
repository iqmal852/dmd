<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shows the password gate. Nothing about the station beyond its printed
 * code is exposed here — no coordinates, no photos, no map. See
 * plan/phases/phase-03-dossier-shell.md M3.3.
 */
class UnlockFormController extends Controller
{
    public function __invoke(Request $request, Station $station): Response
    {
        return Inertia::render('dossier/unlock', [
            'stationPublicId' => $station->public_id,
            'stationCode' => $station->code,
            'error' => session('unlock_error'),
            'redirect' => $request->query('redirect'),
        ]);
    }
}
