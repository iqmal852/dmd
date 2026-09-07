<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AdminDocumentUpdateController extends Controller
{
    public function __invoke(UpdateDocumentRequest $request, Station $station, Media $media): RedirectResponse
    {
        abort_unless($media->collection_name === 'documents', 404);
        abort_if($media->getCustomProperty('document_type') === null, 404);

        DB::transaction(function () use ($request, $station, $media): void {
            $isPrimary = $request->validated('is_primary', false);

            if ($isPrimary) {
                $station->getMedia('documents')
                    ->filter(fn ($other) => $other->id !== $media->id && (bool) $other->getCustomProperty('is_primary'))
                    ->each(function ($other): void {
                        $other->setCustomProperty('is_primary', false);
                        $other->save();
                    });
            }

            $media->setCustomProperty('document_type', $request->validated('document_type'));
            $media->setCustomProperty('title', $request->validated('title'));
            $media->setCustomProperty('revision', $request->validated('revision'));
            $media->setCustomProperty('is_primary', $isPrimary);
            $media->save();
        });

        StationCache::bump($station);

        return back()->with('status', 'Document updated.');
    }
}
