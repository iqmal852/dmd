<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePanoramaRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;

/**
 * `panoramas` is a `singleFile()` collection — uploading a new one
 * automatically replaces (and deletes) the previous, so there is nothing
 * extra to do here to enforce "one panorama per station."
 */
class AdminPanoramaStoreController extends Controller
{
    public function __invoke(StorePanoramaRequest $request, Station $station): RedirectResponse
    {
        $station->addMediaFromRequest('file')
            ->withCustomProperties([
                'initial_yaw' => $request->validated('initial_yaw', 0),
                'initial_pitch' => $request->validated('initial_pitch', 0),
                'hfov' => $request->validated('hfov', 100),
            ])
            ->toMediaCollection('panoramas');

        return back()->with('status', 'Panorama uploaded.');
    }
}
