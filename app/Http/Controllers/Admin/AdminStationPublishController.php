<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The published toggle on the station index — a dedicated endpoint so
 * flipping one boolean doesn't require submitting the full edit form. See
 * plan/phases/phase-08-admin-qr.md M8.2.
 */
class AdminStationPublishController extends Controller
{
    public function __invoke(Request $request, Station $station): RedirectResponse
    {
        Gate::authorize('update', $station);

        $station->update(['is_published' => $request->boolean('is_published')]);

        StationCache::bump($station);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $station->is_published ? "Station {$station->code} is now published. Logged-in users can open its dossier from the QR code." : "Station {$station->code} is now unpublished and its dossier page is hidden.",
        ]);

        return back();
    }
}
