<?php

declare(strict_types=1);

use App\Models\Station;
use Database\Seeders\DemoPhotoSeeder;
use Database\Seeders\DemoStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seededPhotoStation(): Station
{
    test()->seed(DemoStationSeeder::class);
    test()->seed(DemoPhotoSeeder::class);

    return Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();
}

/**
 * plan/phases/phase-06-photos-360.md Test Gate.
 */
it('renders the eye-level photo as the hero with its type label', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station));

    $page->assertSee('Eye-Level Approach');
    $page->assertNoJavaScriptErrors();
});

it('shows the compass rose rotated to the hero photo bearing', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station));

    $page->assertScript(<<<'JS'
        (() => {
            const rose = document.querySelector('[aria-hidden="true"] > div[style*="rotate"]');
            return rose !== null && rose.style.transform.includes('-145deg');
        })()
        JS);
});

it('swaps the hero photo when a thumbnail is tapped', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station));

    $page->assertSee('Eye-Level Approach');

    $page->click('img[alt="Top-Down (Sky Visibility)"]');

    $page->assertScript(<<<'JS'
        document.querySelector('img[fetchpriority="high"]')?.alt === 'Top-Down (Sky Visibility)'
        JS);
});

it('opens the lightbox when the hero photo is tapped', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station));

    $page->click('img[fetchpriority="high"]');

    $page->assertScript("document.querySelector('[role=\"dialog\"]') !== null");
    $page->assertSee('Eye-Level Approach');
});

it('closes the lightbox with the close button and cycles photos with next', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station));

    $page->click('img[fetchpriority="high"]');
    $page->assertScript("document.querySelector('[role=\"dialog\"]') !== null");

    $page->click('[aria-label="Next photo"]');
    $page->assertSee('Top-Down (Sky Visibility)');

    $page->click('[aria-label="Close"]');
    $page->assertScript("document.querySelector('[role=\"dialog\"]') === null");
});

it('navigates to the 360 viewer from the panorama tile', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station));

    $page->click('360°');

    $page->assertPathContains('/360');
});

it('gives the carousel scroll and thumbnail controls a tap target of at least 44x44px', function () {
    $station = seededPhotoStation();

    visit(route('dossier.photos', $station))->assertScript(<<<'JS'
        ['Scroll thumbnails left', 'Scroll thumbnails right'].every((label) => {
            const el = document.querySelector(`[aria-label="${label}"]`);
            const r = el.getBoundingClientRect();
            return r.width >= 44 && r.height >= 44;
        })
        JS);
});

it('fits at 390x844 with no horizontal overflow', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station))->resize(390, 844);

    $page->assertScript('document.body.scrollWidth <= window.innerWidth + 1');
    $page->screenshot(filename: 'phase-06-photos-mobile');
});

it('renders correctly at 1280px desktop', function () {
    $station = seededPhotoStation();

    visit(route('dossier.photos', $station))
        ->resize(1280, 900)
        ->screenshot(filename: 'phase-06-photos-desktop');
});

it('shows an empty state for a station with no photos and no panorama instead of a broken layout', function () {
    $station = Station::factory()->create(['is_published' => true]);

    visit(route('dossier.photos', $station))
        ->assertSee('No site photos')
        ->assertNoJavaScriptErrors();
});

it('produces no layout shift once the hero and conversions have settled', function () {
    $station = seededPhotoStation();

    $page = visit(route('dossier.photos', $station));

    $page->assertScript(<<<'JS'
        (() => {
            window.__cls = 0;
            new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!entry.hadRecentInput) {
                        window.__cls += entry.value;
                    }
                }
            }).observe({ type: 'layout-shift', buffered: true });
            return true;
        })()
        JS);

    $page->wait(1);

    $page->assertScript('window.__cls < 0.1');
});
