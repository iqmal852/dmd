<?php

declare(strict_types=1);

use App\Enums\AccessMode;
use App\Models\Station;
use Database\Seeders\DemoStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * plan/phases/phase-03-dossier-shell.md Test Gate #13 — every poster value
 * for LPT2-GCP-015 must be visible on screen at 390x844, the four tiles
 * present and >=44px, no horizontal overflow.
 */
it('shows every poster value for LPT2-GCP-015 at 390x844 with no horizontal overflow', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    $page = visit(route('dossier.show', $station))->resize(390, 844);

    $page->assertSee('LPT2-GCP-015')
        ->assertSee('Active')
        ->assertSee('KM 318.200')
        ->assertSee('Westbound')
        ->assertSee('4.27412582')
        ->assertSee('128.346 m')
        ->assertSee('428,765.212 m')
        ->assertSee('112.436 m')
        ->assertSee('Coordinates')
        ->assertSee('As-Built')
        ->assertSee('Site Photos')
        ->assertSee('360° View');

    $page->assertScript('document.body.scrollWidth <= window.innerWidth + 1');

    $page->assertScript(<<<'JS'
        Array.from(document.querySelectorAll('a,button')).every((el) => {
            const r = el.getBoundingClientRect();
            return r.width === 0 || (r.width >= 44 && r.height >= 44);
        })
        JS);

    $page->screenshot(filename: 'phase-03-overview-mobile');
});

it('matches the poster at 1280px desktop', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    visit(route('dossier.show', $station))
        ->resize(1280, 800)
        ->screenshot(filename: 'phase-03-overview-desktop');
});

it('renders the overview with no javascript errors', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    visit(route('dossier.show', $station))->assertNoJavaScriptErrors();
});

it('renders the unlock screen with no javascript errors and no station data', function () {
    config([
        'dossier.access_mode' => AccessMode::Password,
        'dossier.access_password' => 'browser-test-secret',
    ]);
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    visit(route('dossier.show', $station))
        ->assertNoJavaScriptErrors()
        ->assertSee('LPT2-GCP-015')
        ->assertDontSee('4.27412582')
        ->assertDontSee('428,765.212');
});
