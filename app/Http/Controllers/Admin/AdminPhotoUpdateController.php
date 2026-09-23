<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePhotoRequest;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AdminPhotoUpdateController extends Controller
{
    public function __invoke(UpdatePhotoRequest $request, Station $station, Media $media): RedirectResponse
    {
        abort_unless($media->collection_name === 'photos', 404);

        $media->setCustomProperty('bearing', $request->validated('bearing'));
        $media->setCustomProperty('label', $request->validated('label'));
        $media->save();

        StationCache::bump($station);

        return back()->with('status', 'Photo updated.');
    }
}
