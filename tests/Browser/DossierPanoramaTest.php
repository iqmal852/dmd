<?php

declare(strict_types=1);

use App\Models\Station;
use Database\Seeders\DemoPhotoSeeder;
use Database\Seeders\DemoStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seededPanoramaStation(): Station
{
    test()->seed(DemoStationSeeder::class);
    test()->seed(DemoPhotoSeeder::class);

    return Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();
}

/**
 * plan/phases/phase-06-photos-360.md Test Gate — M6.5.
 */
it('mounts the pannellum canvas with non-zero dimensions and no JavaScript errors', function () {
    $station = seededPanoramaStation();

    $page = visit(route('dossier.photos.360', $station))->wait(1);

    $page->assertScript(<<<'JS'
        (() => {
            const canvas = document.querySelector('.pnlm-container canvas');
            return canvas !== null && canvas.width > 0 && canvas.height > 0;
        })()
        JS);

    $page->assertNoJavaScriptErrors();
});

it('renders the pannellum compass control', function () {
    $station = seededPanoramaStation();

    $page = visit(route('dossier.photos.360', $station));

    $page->assertScript("document.querySelector('.pnlm-compass') !== null");
});

it('loads the pannellum chunk lazily rather than on the overview page', function () {
    $station = seededPanoramaStation();

    visit(route('dossier.show', $station))
        ->assertScript("typeof window.pannellum === 'undefined'")
        ->assertNoJavaScriptErrors();
});

it('fits at 390x844 with no horizontal overflow', function () {
    $station = seededPanoramaStation();

    $page = visit(route('dossier.photos.360', $station))->resize(390, 844);

    $page->assertScript('document.body.scrollWidth <= window.innerWidth + 1');
    $page->screenshot(filename: 'phase-06-panorama-mobile');
});

it('renders correctly at 1280px desktop', function () {
    $station = seededPanoramaStation();

    visit(route('dossier.photos.360', $station))
        ->resize(1280, 900)
        ->screenshot(filename: 'phase-06-panorama-desktop');
});
