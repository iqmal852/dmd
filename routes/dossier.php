<?php

declare(strict_types=1);

use App\Http\Controllers\Dossier\DossierCoordinatesController;
use App\Http\Controllers\Dossier\DossierOverviewController;
use App\Http\Controllers\Dossier\UnlockFormController;
use App\Http\Controllers\Dossier\UnlockSubmitController;
use App\Http\Middleware\AddNoindexHeader;
use App\Http\Middleware\EnsureDossierUnlocked;
use App\Models\Station;
use Illuminate\Support\Facades\Route;

/*
 | Route-model binding resolves {station} on the ULID public_id, scoped to
 | published stations only. An unpublished or soft-deleted station 404s —
 | never "this exists but you can't see it" (a QR plate should not confirm
 | the existence of an unpublished record). See
 | plan/phases/phase-03-dossier-shell.md M3.1.
 */
Route::bind('station', fn (string $value) => Station::query()
    ->where('public_id', $value)
    ->where('is_published', true)
    ->firstOrFail());

Route::prefix(config('dossier.route_prefix'))
    ->middleware(AddNoindexHeader::class)
    ->name('dossier.')
    ->group(function (): void {
        Route::get('{station}/unlock', UnlockFormController::class)->name('unlock');
        Route::post('{station}/unlock', UnlockSubmitController::class)
            ->middleware('throttle:dossier-unlock')
            ->name('unlock.submit');

        Route::middleware(EnsureDossierUnlocked::class)->group(function (): void {
            Route::get('{station}', DossierOverviewController::class)->name('show');
            Route::get('{station}/coordinates', DossierCoordinatesController::class)->name('coordinates');
            // map / photos / files added in Phases 05-07.
        });
    });
