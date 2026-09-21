<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStationRequest;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;

/**
 * See plan/phases/phase-08-admin-qr.md M8.3. Every field is set explicitly
 * from the validated payload — never `$request->validated()` spread
 * directly into `update()` — so `public_id` can never be influenced by a
 * submitted payload no matter what the request contains.
 */
class AdminStationUpdateController extends Controller
{
    public function __invoke(UpdateStationRequest $request, Station $station): RedirectResponse
    {
        $station->fill([
            'code' => $request->validated('code'),
            'gcp_reference' => $request->validated('gcp_reference'),
            'highway' => $request->validated('highway'),
            'section' => $request->validated('section'),
            'location' => $request->validated('location'),
            'km' => $request->validated('km'),
            'direction' => $request->validated('direction'),
            'facility_type' => $request->validated('facility_type'),
            'monument_type' => $request->validated('monument_type'),
            'installed_at' => $request->validated('installed_at'),
            'status' => $request->validated('status'),
            'description' => $request->validated('description'),
            'is_published' => $request->validated('is_published', false),
        ]);

        if ($request->clearAccessPassword()) {
            $station->access_password = null;
        } elseif ($request->validated('access_password')) {
            $station->access_password = $request->validated('access_password');
        }

        $station->save();

        StationCache::bump($station);

        return to_route('admin.stations.edit', $station)
            ->with('status', 'Station updated.');
    }
}
