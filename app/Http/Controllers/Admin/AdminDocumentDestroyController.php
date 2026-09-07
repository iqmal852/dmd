<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Deleting a document also deletes its paired preview row, if it has one
 * — an orphaned preview-only row would otherwise linger forever, invisible
 * on the Files screen and unreachable from the admin UI. See
 * plan/phases/phase-08-admin-qr.md M8.7.
 */
class AdminDocumentDestroyController extends Controller
{
    public function __invoke(Station $station, Media $media): RedirectResponse
    {
        Gate::authorize('update', $station);

        abort_unless($media->collection_name === 'documents', 404);

        $previewMediaId = $media->getCustomProperty('preview_media_id');

        if ($previewMediaId !== null) {
            $station->getMedia('documents')->firstWhere('id', (int) $previewMediaId)?->delete();
        }

        $media->delete();

        return back()->with('status', 'Document deleted.');
    }
}
