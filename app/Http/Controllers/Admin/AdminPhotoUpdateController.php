<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePhotoRequest;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AdminPhotoUpdateController extends Controller
{
    public function __invoke(UpdatePhotoRequest $request, Station $station, Media $media): RedirectResponse
    {
        abort_unless($media->collection_name === 'photos', 404);

        $media->setCustomProperty('photo_type', $request->validated('photo_type'));
        $media->setCustomProperty('bearing', $request->validated('bearing'));
        $media->setCustomProperty('caption', $request->validated('caption'));
        $media->save();

        return back()->with('status', 'Photo updated.');
    }
}
