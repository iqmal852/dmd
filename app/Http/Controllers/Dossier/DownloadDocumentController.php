<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dossier;

use App\Events\DocumentDownloaded;
use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the original as-built file — never a public disk URL. Bound and
 * scoped via `{station}/{media:uuid}` + `scopeBindings()` in
 * routes/dossier.php, so a media row from another station 404s here
 * rather than being served. See plan/phases/phase-07-asbuilt-files.md M7.4.
 */
class DownloadDocumentController extends Controller
{
    public function __invoke(Request $request, Station $station, Media $media): StreamedResponse
    {
        abort_unless($media->collection_name === 'documents', 404);

        $filename = $this->filenameFor($station, $media);

        $response = Storage::disk($media->disk)->download(
            $media->getPathRelativeToRoot(),
            $filename,
            [
                'Content-Type' => $media->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=0, no-store',
            ],
        );

        DocumentDownloaded::dispatch($station, $media, $request->ip() ?? '', $request->userAgent());

        return $response;
    }

    /**
     * Built from the station code, document type, and revision — never
     * from a user-supplied filename, which is how path traversal and
     * header injection get in. Each part is slugged independently.
     */
    private function filenameFor(Station $station, Media $media): string
    {
        $parts = array_filter([
            $station->code,
            (string) $media->getCustomProperty('document_type'),
            $media->getCustomProperty('revision') !== null
                ? 'rev-'.$media->getCustomProperty('revision')
                : null,
        ]);

        $slug = collect($parts)
            ->map(fn (string $part) => Str::slug($part))
            ->implode('-');

        return $slug.'.'.$media->extension;
    }
}
