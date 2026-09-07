<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

// Neumorphism kitchen sink — every design-system primitive, every variant.
// Local/testing only: this is a development reference, never a real page.
// 'testing' is included so Phase 02's automated browser tests can reach it;
// it is never present in staging or production. See
// plan/phases/phase-02-design-system.md M2.6.
if (app()->environment(['local', 'testing'])) {
    Route::inertia('dev/ui', 'dev/ui')->name('dev.ui');
}

require __DIR__.'/settings.php';
require __DIR__.'/dossier.php';
