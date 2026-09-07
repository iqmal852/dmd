<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

/*
 | A physical `public/robots.txt` also exists so a real web server (Nginx,
 | Apache) serves it directly without invoking PHP at all — that file
 | takes priority there and must be kept in sync with this route by hand
 | (see docs/DEPLOYMENT.md's QR-domain-change warning for the same class
 | of "update both places" risk). This route exists so `php artisan serve`
 | and the test suite — neither of which serve `public/` as static files
 | the way a production web server does — see the same, always-correct,
 | config-driven content. See plan/phases/phase-09-hardening-release.md
 | M9.1.
 */
Route::get('robots.txt', function () {
    $prefix = trim((string) config('dossier.route_prefix'), '/');

    return response("User-agent: *\nDisallow: /{$prefix}/\nDisallow: /admin/\n")
        ->header('Content-Type', 'text/plain');
})->name('robots');

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
require __DIR__.'/admin.php';
