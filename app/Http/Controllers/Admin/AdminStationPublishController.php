<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        return back();
    }
}
