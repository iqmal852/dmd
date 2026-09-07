<?php

declare(strict_types=1);

use App\Models\Station;
use Database\Seeders\DemoDocumentSeeder;
use Database\Seeders\DemoStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seededDocumentStations(): array
{
    test()->seed(DemoStationSeeder::class);
    test()->seed(DemoDocumentSeeder::class);

    return [
        Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail(),
        Station::query()->where('code', 'LPT2-GCP-001')->firstOrFail(),
        Station::query()->where('code', 'LPT2-GCP-010')->firstOrFail(),
    ];
}

/**
 * plan/phases/phase-07-asbuilt-files.md Test Gate.
 */
it('renders the primary document card with its own PDF as the preview', function () {
    [$ownPreview] = seededDocumentStations();

    $page = visit(route('dossier.files', $ownPreview));

    $page->assertSee('As-Built Drawing');
    $page->assertSee('Rev. A');
    $page->assertSee('PDF');
    $page->assertScript("document.querySelector('object[type=\"application/pdf\"]') !== null");
    $page->assertNoJavaScriptErrors();
});

it('renders a compact row for each non-primary document', function () {
    [$ownPreview] = seededDocumentStations();

    visit(route('dossier.files', $ownPreview))
        ->assertSee('QC Report')
        ->assertSee('Site Acceptance Certificate');
});

it('opens the primary image document preview in the lightbox when tapped', function () {
    [, , $imagePreview] = seededDocumentStations();

    $page = visit(route('dossier.files', $imagePreview));

    $page->click('img[alt="As-Built Drawing preview"]');

    $page->assertScript("document.querySelector('[role=\"dialog\"]') !== null");

    $page->click('[aria-label="Close"]');
    $page->assertScript("document.querySelector('[role=\"dialog\"]') === null");
});

it('renders the DWG originals paired preview instead of the unrenderable original', function () {
    [, $separatePreview] = seededDocumentStations();

    $page = visit(route('dossier.files', $separatePreview));

    $page->assertSee('As-Built Drawing');
    $page->assertSee('Rev. B');
    $page->assertScript("document.querySelector('object[type=\"application/pdf\"]') !== null");
    $page->assertNoJavaScriptErrors();
});

it('gives the download button a tap target of at least 44x44px', function () {
    [$ownPreview] = seededDocumentStations();

    visit(route('dossier.files', $ownPreview))->assertScript(<<<'JS'
        (() => {
            const btn = [...document.querySelectorAll('button')].find((b) => b.textContent.includes('Download As-Built Drawing'));
            if (!btn) return false;
            const r = btn.getBoundingClientRect();
            return r.width >= 44 && r.height >= 44;
        })()
        JS);
});

it('downloads the primary document when the download button is followed', function () {
    [$ownPreview] = seededDocumentStations();

    $page = visit(route('dossier.files', $ownPreview));

    $page->assertScript(<<<'JS'
        (() => {
            const btn = [...document.querySelectorAll('button')].find((b) => b.textContent.includes('Download As-Built Drawing'));
            const anchor = btn?.closest('a');
            return anchor !== null && anchor?.getAttribute('href')?.includes('/download');
        })()
        JS);
});

it('fits at 390x844 with no horizontal overflow', function () {
    [$ownPreview] = seededDocumentStations();

    $page = visit(route('dossier.files', $ownPreview))->resize(390, 844);

    $page->assertScript('document.body.scrollWidth <= window.innerWidth + 1');
    $page->screenshot(filename: 'phase-07-files-mobile');
});

it('renders correctly at 1280px desktop', function () {
    [$ownPreview] = seededDocumentStations();

    visit(route('dossier.files', $ownPreview))
        ->resize(1280, 900)
        ->screenshot(filename: 'phase-07-files-desktop');
});

it('shows an empty state for a station with no documents instead of a broken layout', function () {
    $station = Station::factory()->create(['is_published' => true]);

    visit(route('dossier.files', $station))
        ->assertSee('As-built drawing not yet uploaded')
        ->assertNoJavaScriptErrors();
});
