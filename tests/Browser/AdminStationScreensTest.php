<?php

declare(strict_types=1);

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Evidence screenshots for plan/phases/phase-08-admin-qr.md M8.2/M8.3 —
 * not exhaustive functional coverage (that's AdminStationCrudTest and
 * AdminMediaTest at the HTTP layer), just visual proof the two primary
 * screens render correctly end to end.
 */
it('renders the station index', function () {
    $user = User::factory()->create(['password' => 'correct-password']);
    Station::factory()->count(5)->create();

    $page = visit(route('login'));
    $page->fill('email', $user->email);
    $page->fill('password', 'correct-password');
    $page->click('Log in');
    $page->assertPathIs('/admin/stations');

    $page->assertNoJavaScriptErrors();
    $page->resize(1280, 900);
    $page->screenshot(filename: 'phase-08-station-index');
});

it('renders the station edit form with all tabs reachable', function () {
    $user = User::factory()->create(['password' => 'correct-password']);
    $station = Station::factory()->create(['code' => 'SCR-GCP-001']);

    $page = visit(route('login'));
    $page->fill('email', $user->email);
    $page->fill('password', 'correct-password');
    $page->click('Log in');

    $page = visit(route('admin.stations.edit', $station));
    $page->assertSee('SCR-GCP-001');
    $page->assertNoJavaScriptErrors();
    $page->resize(1280, 900);
    $page->screenshot(filename: 'phase-08-station-edit');

    foreach (['Coordinates', 'Specification', 'Photos', '360° Panorama', 'Documents'] as $tab) {
        $page->click($tab);
        $page->assertNoJavaScriptErrors();
    }
});
