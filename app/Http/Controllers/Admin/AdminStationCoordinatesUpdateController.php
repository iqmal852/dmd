<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCoordinateSetRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;

/**
 * Creates or updates the station's single CoordinateSet (a hasOne) — see
 * plan/phases/phase-08-admin-qr.md M8.6.
 */
class AdminStationCoordinatesUpdateController extends Controller
{
    public function __invoke(UpdateCoordinateSetRequest $request, Station $station): RedirectResponse
    {
        $station->coordinateSet()->updateOrCreate(
            ['station_id' => $station->id],
            $request->validated(),
        );

        return back()->with('status', 'Coordinates updated.');
    }
}
