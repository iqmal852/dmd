<?php

declare(strict_types=1);

use App\Http\Controllers\Dossier\DossierCoordinatesController;
use App\Http\Controllers\Dossier\DossierFilesController;
use App\Http\Controllers\Dossier\DossierMapController;
use App\Http\Controllers\Dossier\DossierOverviewController;
use App\Http\Controllers\Dossier\DossierPhotosController;
use App\Http\Controllers\Dossier\DownloadDocumentController;
use App\Http\Controllers\Dossier\PreviewDocumentController;
use App\Http\Controllers\Dossier\UnlockFormController;
use App\Http\Controllers\Dossier\UnlockSubmitController;
use App\Http\Middleware\AddNoindexHeader;
use App\Http\Middleware\EnsureDossierUnlocked;
use App\Http\Middleware\EnsureStationIsPublished;
use Illuminate\Support\Facades\Route;

/*
 | {station} resolves on the ULID public_id via Station::getRouteKeyName()
 | (default implicit binding — the same as every admin route). This group
 | additionally requires is_published via EnsureStationIsPublished: an
 | unpublished or soft-deleted station 404s here — never "this exists but
 | you can't see it" (a QR plate should not confirm the existence of an
 | unpublished record) — without that restriction leaking onto unrelated
 | routes the way a global Route::bind() would. See
 | plan/phases/phase-03-dossier-shell.md M3.1 and
 | plan/phases/phase-08-admin-qr.md (which found the leak).
 |
 | Every route here also requires a logged-in user. A guest who scans a QR
 | plate is sent to /login and, once authenticated, returned to the dossier
 | page they scanned (the `auth` middleware stores the intended URL and
 | Fortify's login response redirects to it before falling back to home).
 | `auth` runs ahead of station binding, so a guest cannot probe which
 | station IDs exist or are published.
 */
Route::prefix(config('dossier.route_prefix'))
    ->middleware(['auth', AddNoindexHeader::class, EnsureStationIsPublished::class])
    ->name('dossier.')
    ->group(function (): void {
        Route::get('{station}/unlock', UnlockFormController::class)->name('unlock');
        Route::post('{station}/unlock', UnlockSubmitController::class)
            ->middleware('throttle:dossier-unlock')
            ->name('unlock.submit');

        Route::middleware(EnsureDossierUnlocked::class)->group(function (): void {
            Route::get('{station}', DossierOverviewController::class)->name('show');
            Route::get('{station}/coordinates', DossierCoordinatesController::class)->name('coordinates');
            Route::get('{station}/map', DossierMapController::class)->name('map');
            Route::get('{station}/photos', DossierPhotosController::class)->name('photos');
            Route::get('{station}/files', DossierFilesController::class)->name('files');

            // {media:uuid} + scopeBindings() means a media row belonging to a
            // different station 404s here rather than being served — see
            // plan/phases/phase-07-asbuilt-files.md M7.4.
            Route::get('{station}/files/{media:uuid}/preview', PreviewDocumentController::class)
                ->scopeBindings()
                ->name('files.preview');
            Route::get('{station}/files/{media:uuid}/download', DownloadDocumentController::class)
                ->middleware('throttle:dossier-download')
                ->scopeBindings()
                ->name('files.download');
        });
    });
