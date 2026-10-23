<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCoordinateSetRequest;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

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

        StationCache::bump($station);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinates were updated successfully. Your changes have been saved.']);

        return back();
    }
}
