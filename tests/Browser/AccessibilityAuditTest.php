<?php

declare(strict_types=1);

use App\Enums\AccessMode;
use App\Models\Station;
use Database\Seeders\DemoDocumentSeeder;
use Database\Seeders\DemoPhotoSeeder;
use Database\Seeders\DemoStationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * plan/phases/phase-09-hardening-release.md M9.3 — axe-core over every
 * public route in light and dark, zero serious/critical violations.
 * `level: 1` is "serious" (axe's scale is critical=0 through minor=3),
 * matching that requirement exactly — see
 * plan/phases/phase-02-design-system.md's DevUiKitchenSinkTest, the
 * first place this app used axe-core.
 *
 * One richly-seeded reference station (LPT2-GCP-015 — coordinates,
 * specification, photos, a 360° panorama, and an as-built document) so
 * every route's real content — not an empty state — is what gets
 * audited.
 */
function seededReferenceStation(): Station
{
    test()->seed(DemoStationSeeder::class);
    test()->seed(DemoPhotoSeeder::class);
    test()->seed(DemoDocumentSeeder::class);

    return Station::query()->where('code', 'LPT2-GCP-015')->firstOrFail();
}

$publicRoutes = [
    'dossier overview' => fn (Station $station) => route('dossier.show', $station),
    'dossier coordinates' => fn (Station $station) => route('dossier.coordinates', $station),
    'dossier map' => fn (Station $station) => route('dossier.map', $station),
    'dossier photos' => fn (Station $station) => route('dossier.photos', $station),
    'dossier 360 panorama' => fn (Station $station) => route('dossier.photos.360', $station),
    'dossier files' => fn (Station $station) => route('dossier.files', $station),
];

foreach ($publicRoutes as $label => $urlFor) {
    it("has no serious or critical accessibility violations on {$label} in light mode", function () use ($urlFor) {
        $station = seededReferenceStation();

        visit($urlFor($station))
            ->inLightMode()
            ->assertNoAccessibilityIssues(level: 1);
    });

    it("has no serious or critical accessibility violations on {$label} in dark mode", function () use ($urlFor) {
        $station = seededReferenceStation();

        visit($urlFor($station))
            ->inDarkMode()
            ->assertNoAccessibilityIssues(level: 1);
    });
}

it('has no serious or critical accessibility violations on the unlock screen', function () {
    config([
        'dossier.access_mode' => AccessMode::Password,
        'dossier.access_password' => 'secret',
    ]);
    $station = seededReferenceStation();

    visit(route('dossier.show', $station))
        ->inLightMode()
        ->assertNoAccessibilityIssues(level: 1);
});

it('has no serious or critical accessibility violations on the login screen', function () {
    visit(route('login'))
        ->inLightMode()
        ->assertNoAccessibilityIssues(level: 1);
});
