<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Data\DocumentData;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a document's on-screen preview inline (never as an attachment) —
 * the PDF or image itself, at full resolution, whether it's the original
 * file or a separately-uploaded preview for a DWG/DXF original. Bound and
 * scoped the same way as DownloadDocumentController. See
 * plan/phases/phase-07-asbuilt-files.md M7.3/M7.4.
 */
class PreviewDocumentController extends Controller
{
    public function __invoke(Station $station, Media $media): StreamedResponse
    {
        abort_unless($media->collection_name === 'documents', 404);
        abort_unless(DocumentData::isRenderableMime($media->mime_type), 404);

        return Storage::disk($media->disk)->response(
            $media->getPathRelativeToRoot(),
            null,
            [
                'Content-Type' => $media->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=0, no-store',
            ],
        );
    }
}
