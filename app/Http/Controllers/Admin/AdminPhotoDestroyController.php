<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AdminPhotoDestroyController extends Controller
{
    public function __invoke(Station $station, Media $media): RedirectResponse
    {
        Gate::authorize('update', $station);

        abort_unless($media->collection_name === 'photos', 404);

        $media->delete();

        StationCache::bump($station);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Photo deleted. It has been removed from this station's photo gallery."]);

        return back();
    }
}
