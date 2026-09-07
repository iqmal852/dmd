<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Soft delete only (Station uses SoftDeletes) — a station is never hard
 * deleted through the admin UI, matching "Secure & Read-Only" and keeping
 * the audit trail intact. See plan/phases/phase-08-admin-qr.md M8.2.
 */
class AdminStationDestroyController extends Controller
{
    public function __invoke(Station $station): RedirectResponse
    {
        Gate::authorize('delete', $station);

        $station->delete();

        return to_route('admin.stations.index')
            ->with('status', "Station {$station->code} deleted.");
    }
}
