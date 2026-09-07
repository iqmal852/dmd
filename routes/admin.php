<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminDocumentDestroyController;
use App\Http\Controllers\Admin\AdminDocumentStoreController;
use App\Http\Controllers\Admin\AdminDocumentUpdateController;
use App\Http\Controllers\Admin\AdminPanoramaDestroyController;
use App\Http\Controllers\Admin\AdminPanoramaStoreController;
use App\Http\Controllers\Admin\AdminPhotoDestroyController;
use App\Http\Controllers\Admin\AdminPhotoStoreController;
use App\Http\Controllers\Admin\AdminPhotoUpdateController;
use App\Http\Controllers\Admin\AdminQrController;
use App\Http\Controllers\Admin\AdminQrDownloadController;
use App\Http\Controllers\Admin\AdminQrPrintController;
use App\Http\Controllers\Admin\AdminQrSheetController;
use App\Http\Controllers\Admin\AdminSpecificationUpdateController;
use App\Http\Controllers\Admin\AdminStationCoordinatesUpdateController;
use App\Http\Controllers\Admin\AdminStationCreateController;
use App\Http\Controllers\Admin\AdminStationDestroyController;
use App\Http\Controllers\Admin\AdminStationEditController;
use App\Http\Controllers\Admin\AdminStationIndexController;
use App\Http\Controllers\Admin\AdminStationPublishController;
use App\Http\Controllers\Admin\AdminStationStoreController;
use App\Http\Controllers\Admin\AdminStationUpdateController;
use App\Http\Middleware\EnsureIsAdmin;
use Illuminate\Support\Facades\Route;

/*
 | Every route here sits behind ['auth', EnsureIsAdmin::class] — see
 | plan/phases/phase-08-admin-qr.md M8.1. `{station}` binds on `public_id`
 | (Station::getRouteKeyName()) everywhere, same as the public dossier
 | routes, so an admin URL never leaks the numeric primary key either.
 */
Route::prefix('admin')
    ->middleware(['auth', EnsureIsAdmin::class])
    ->name('admin.')
    ->group(function (): void {
        Route::get('stations', AdminStationIndexController::class)->name('stations.index');
        Route::get('stations/create', AdminStationCreateController::class)->name('stations.create');
        Route::post('stations', AdminStationStoreController::class)->name('stations.store');
        Route::get('stations/{station}/edit', AdminStationEditController::class)->name('stations.edit');
        Route::put('stations/{station}', AdminStationUpdateController::class)->name('stations.update');
        Route::delete('stations/{station}', AdminStationDestroyController::class)->name('stations.destroy');
        Route::patch('stations/{station}/publish', AdminStationPublishController::class)->name('stations.publish');

        Route::put('stations/{station}/coordinates', AdminStationCoordinatesUpdateController::class)->name('stations.coordinates.update');
        Route::put('stations/{station}/specification', AdminSpecificationUpdateController::class)->name('stations.specification.update');

        Route::post('stations/{station}/photos', AdminPhotoStoreController::class)->name('stations.photos.store');
        Route::patch('stations/{station}/photos/{media:uuid}', AdminPhotoUpdateController::class)->scopeBindings()->name('stations.photos.update');
        Route::delete('stations/{station}/photos/{media:uuid}', AdminPhotoDestroyController::class)->scopeBindings()->name('stations.photos.destroy');

        Route::post('stations/{station}/panorama', AdminPanoramaStoreController::class)->name('stations.panorama.store');
        Route::delete('stations/{station}/panorama/{media:uuid}', AdminPanoramaDestroyController::class)->scopeBindings()->name('stations.panorama.destroy');

        Route::post('stations/{station}/documents', AdminDocumentStoreController::class)->name('stations.documents.store');
        Route::patch('stations/{station}/documents/{media:uuid}', AdminDocumentUpdateController::class)->scopeBindings()->name('stations.documents.update');
        Route::delete('stations/{station}/documents/{media:uuid}', AdminDocumentDestroyController::class)->scopeBindings()->name('stations.documents.destroy');

        Route::get('stations/{station}/qr', AdminQrController::class)->name('stations.qr');
        Route::get('stations/{station}/qr/download', AdminQrDownloadController::class)->name('stations.qr.download');
        Route::get('stations/{station}/qr/print', AdminQrPrintController::class)->name('stations.qr.print');
        Route::get('qr/sheet', AdminQrSheetController::class)->name('qr.sheet');
    });
