<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\DocumentData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Picture1.png panel 3 — see plan/phases/phase-07-asbuilt-files.md M7.2.
 */
class DossierFilesController extends Controller
{
    public function __invoke(Station $station): Response
    {
        $documents = $station->getMedia('documents')
            // A media row with no `document_type` is a preview-only
            // attachment for a DWG/DXF original (linked via that other
            // row's `preview_media_id`), not a document in its own right —
            // see plan/phases/phase-07-asbuilt-files.md M7.1.
            ->filter(fn ($media) => $media->getCustomProperty('document_type') !== null)
            ->sortByDesc(fn ($media) => (bool) $media->getCustomProperty('is_primary'))
            ->values()
            ->map(fn ($media) => DocumentData::from($media, $station))
            ->all();

        return Inertia::render('dossier/files', [
            'stationPublicId' => $station->public_id,
            'stationCode' => $station->code,
            'documents' => $documents,
        ]);
    }
}
