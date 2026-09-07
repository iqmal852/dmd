<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Data\Admin\StationFormData;
use App\Enums\Direction;
use App\Enums\StationStatus;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * See plan/phases/phase-08-admin-qr.md M8.3.
 */
class AdminStationCreateController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/stations/form', [
            'station' => StationFormData::empty(),
            'directionOptions' => collect(Direction::cases())
                ->map(fn (Direction $direction) => ['value' => $direction->value, 'label' => $direction->label()]),
            'statusOptions' => collect(StationStatus::cases())
                ->map(fn (StationStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
        ]);
    }
}
