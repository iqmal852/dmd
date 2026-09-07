<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePhotoRequest;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;

class AdminPhotoStoreController extends Controller
{
    public function __invoke(StorePhotoRequest $request, Station $station): RedirectResponse
    {
        $file = $request->file('file');
        $dimensions = $file ? @getimagesize($file->getRealPath()) : false;

        $station->addMediaFromRequest('file')
            ->withCustomProperties([
                'photo_type' => $request->validated('photo_type'),
                'bearing' => $request->validated('bearing'),
                'caption' => $request->validated('caption'),
                'captured_at' => now()->toDateString(),
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
            ])
            ->toMediaCollection('photos');

        StationCache::bump($station);

        return back()->with('status', 'Photo uploaded.');
    }
}
