<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSpecificationRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;

/**
 * See plan/phases/phase-08-admin-qr.md M8.6.
 */
class AdminSpecificationUpdateController extends Controller
{
    public function __invoke(UpdateSpecificationRequest $request, Station $station): RedirectResponse
    {
        $station->specification()->updateOrCreate(
            ['station_id' => $station->id],
            $request->validated(),
        );

        return back()->with('status', 'Specification updated.');
    }
}
