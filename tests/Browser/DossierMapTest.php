<?php

declare(strict_types=1);

use App\Models\Station;
use Database\Seeders\DemoStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * plan/phases/phase-05-location-map.md Test Gate #7/#8.
 */
it('renders exactly one pin with attribution present for both layers', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    $page = visit(route('dossier.map', $station));

    $page->assertScript(
        "document.querySelectorAll('.leaflet-marker-icon').length",
        1,
    );

    $page->assertSee('Esri');

    $page->click('button[aria-label="Toggle map layer"]');
    $page->assertSee('OpenStreetMap');
});

it('gives the zoom and layer controls a tap target of at least 44x44px', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    visit(route('dossier.map', $station))->assertScript(<<<'JS'
        ['Zoom in', 'Zoom out', 'Recentre map', 'Toggle map layer'].every((label) => {
            const el = document.querySelector(`[aria-label="${label}"]`);
            const r = el.getBoundingClientRect();
            return r.width >= 44 && r.height >= 44;
        })
        JS);
});

it('fits at 390x844 with no horizontal overflow and the map not hidden behind the bottom nav', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    $page = visit(route('dossier.map', $station))->resize(390, 844);

    $page->assertScript('document.body.scrollWidth <= window.innerWidth + 1');

    $page->assertScript(<<<'JS'
        (() => {
            const mapBottom = document.querySelector('.leaflet-container').getBoundingClientRect().bottom;
            const nav = document.querySelector('nav[aria-label="Dossier sections"]');
            const navTop = nav.getBoundingClientRect().top;
            return mapBottom <= navTop;
        })()
        JS);

    $page->screenshot(filename: 'phase-05-map-mobile');
});

it('renders correctly at 1280px desktop', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    visit(route('dossier.map', $station))
        ->resize(1280, 900)
        ->screenshot(filename: 'phase-05-map-desktop');
});

it('renders the overview map preview without loading leaflet', function () {
    $this->seed(DemoStationSeeder::class);
    $station = Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();

    visit(route('dossier.show', $station))
        ->assertScript("typeof window.L === 'undefined'")
        ->assertNoJavaScriptErrors();
});

it('shows an empty state for a station with no coordinates instead of a broken map', function () {
    $station = Station::factory()->create(['is_published' => true]);

    visit(route('dossier.map', $station))
        ->assertSee('the map cannot be')
        ->assertNoJavaScriptErrors();
});
