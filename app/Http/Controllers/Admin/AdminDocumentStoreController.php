<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Models\Station;
use App\Services\StationCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * See plan/phases/phase-08-admin-qr.md M8.7.
 */
class AdminDocumentStoreController extends Controller
{
    public function __invoke(StoreDocumentRequest $request, Station $station): RedirectResponse
    {
        DB::transaction(function () use ($request, $station): void {
            $isPrimary = $request->validated('is_primary', false);

            if ($isPrimary) {
                $this->clearExistingPrimary($station);
            }

            $previewMediaId = null;

            if ($request->hasFile('preview_file')) {
                $preview = $station->addMediaFromRequest('preview_file')
                    ->withCustomProperties(['is_preview_only' => true])
                    ->toMediaCollection('documents');

                $previewMediaId = $preview->id;
            }

            $station->addMediaFromRequest('file')
                ->withCustomProperties([
                    'document_type' => $request->validated('document_type'),
                    'title' => $request->validated('title'),
                    'revision' => $request->validated('revision'),
                    'is_primary' => $isPrimary,
                    'preview_media_id' => $previewMediaId,
                ])
                ->toMediaCollection('documents');
        });

        StationCache::bump($station);

        return back()->with('status', 'Document uploaded.');
    }

    private function clearExistingPrimary(Station $station): void
    {
        $station->getMedia('documents')
            ->filter(fn ($media) => (bool) $media->getCustomProperty('is_primary'))
            ->each(function ($media): void {
                $media->setCustomProperty('is_primary', false);
                $media->save();
            });
    }
}
