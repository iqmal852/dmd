<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePhotoRequest;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;

/**
 * Accepts every file from one multi-select in a single request — see
 * StorePhotoRequest's own docblock. The cache is bumped once after the
 * whole batch, not per file.
 */
class AdminPhotoStoreController extends Controller
{
    public function __invoke(StorePhotoRequest $request, Station $station): RedirectResponse
    {
        $files = $request->file('files', []);
        $labels = $request->validated('labels', []);
        $count = count($files);

        foreach ($files as $index => $file) {
            $dimensions = @getimagesize($file->getRealPath());

            $station->addMedia($file)
                ->withCustomProperties([
                    'label' => $labels[$index] ?? null,
                    'bearing' => null,
                    'captured_at' => now()->toDateString(),
                    'width' => $dimensions[0] ?? null,
                    'height' => $dimensions[1] ?? null,
                ])
                ->toMediaCollection('photos');
        }

        StationCache::bump($station);

        return back()->with('status', $count === 1 ? 'Photo uploaded.' : "{$count} photos uploaded.");
    }
}
