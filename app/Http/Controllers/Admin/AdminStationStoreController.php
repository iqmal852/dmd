<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStationRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;

/**
 * See plan/phases/phase-08-admin-qr.md M8.3. `public_id` is never set from
 * the request — it is assigned in Station::booted() — so there is no way
 * for a submitted payload to influence it.
 */
class AdminStationStoreController extends Controller
{
    public function __invoke(StoreStationRequest $request): RedirectResponse
    {
        $station = Station::query()->create([
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
            'access_password' => $request->validated('access_password'),
            'is_published' => $request->validated('is_published', false),
        ]);

        return to_route('admin.stations.edit', $station)
            ->with('status', 'Station created.');
    }
}
