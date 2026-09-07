<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Data\Admin\StationListItemData;
use App\Enums\StationStatus;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Search, filter, sort — all state lives in the URL query string via
 * Inertia so it survives a refresh and is shareable. See
 * plan/phases/phase-08-admin-qr.md M8.2.
 */
class AdminStationIndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $sort = in_array($request->query('sort'), ['km', 'code', 'highway'], true)
            ? $request->query('sort')
            : 'km';

        $stations = Station::query()
            ->with(['coordinateSet', 'media'])
            ->when($request->filled('search'), fn ($query) => $query->where('code', 'ilike', '%'.$request->query('search').'%'))
            ->when($request->filled('highway'), fn ($query) => $query->where('highway', $request->query('highway')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderBy($sort)
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('admin/stations/index', [
            'stations' => $stations->through(fn (Station $station) => StationListItemData::from($station)),
            'filters' => [
                'search' => $request->query('search', ''),
                'highway' => $request->query('highway', ''),
                'status' => $request->query('status', ''),
                'sort' => $sort,
            ],
            'highways' => Station::query()->distinct()->orderBy('highway')->pluck('highway'),
            'statusOptions' => collect(StationStatus::cases())
                ->map(fn (StationStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
        ]);
    }
}
