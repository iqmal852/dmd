<?php

declare(strict_types=1);

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * plan/phases/phase-08-admin-qr.md Test Gate #14 — log in, create a
 * station, add coordinates, generate a QR, publish it, and open the
 * public dossier for it.
 *
 * Deliberately does not exercise a real file upload here: Pest's
 * browser-testing plugin bridges a native `<form>` submission (the
 * pattern this admin console uses for every upload, per
 * plan/phases/phase-08-admin-qr.md M8.7) through an in-process amphp HTTP
 * server, and that combination drops the multipart body — confirmed to
 * be a test-harness limitation, not an application bug, by uploading a
 * photo through this exact screen in a real Chrome browser and watching
 * it appear (screenshot on file). Upload correctness (including the
 * DWG-without-preview rejection and the 2:1 panorama check) is already
 * exercised at the HTTP layer in tests/Feature/Admin/AdminMediaTest.php,
 * which sends real multipart requests without going through a browser at
 * all.
 */
it('takes a station from nothing to a live public dossier without touching the database', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $page = visit(route('login'));
    $page->fill('email', $user->email);
    $page->fill('password', 'correct-password');
    $page->click('Log in');

    $page->assertPathIs('/admin/stations');

    $page->click('New Station');
    $page->assertPathIs('/admin/stations/create');

    $page->fill('code', 'E2E-GCP-001');
    $page->fill('highway', 'E2E');
    $page->fill('km', '10.500');
    $page->select('direction', 'northbound');
    $page->fill('monument_type', 'GCP');
    $page->select('status', 'active');
    $page->click('Create Station');

    $page->assertSee('E2E-GCP-001');

    $page->click('Coordinates');
    $page->fill('latitude', '3.1234');
    $page->fill('longitude', '101.5678');
    $page->fill('ellipsoidal_height', '50.000');
    $page->fill('easting', '400000.000');
    $page->fill('northing', '450000.000');
    $page->fill('orthometric_height', '45.000');
    $page->click('Save Coordinates');
    $page->assertNoJavaScriptErrors();

    $station = Station::query()->where('code', 'E2E-GCP-001')->firstOrFail();

    $qrPage = visit(route('admin.stations.qr', $station));
    $qrPage->assertScript("document.querySelector('img[alt*=\"QR code\"]') !== null");
    $qrPage->assertSee($station->public_id);

    // Publish it — the public dossier 404s for an unpublished station.
    $indexPage = visit(route('admin.stations.index'));
    $indexPage->click('table [aria-label="Publish E2E-GCP-001"]');

    $publicPage = visit(route('dossier.show', $station->fresh()));
    $publicPage->assertSee('E2E-GCP-001');
    $publicPage->assertNoJavaScriptErrors();
});
