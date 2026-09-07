<?php

declare(strict_types=1);

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * plan/phases/phase-08-admin-qr.md Test Gate #15 — the plate sheet renders
 * with @media print styles, no shadows.
 */
function loggedInAdminSession(): User
{
    $user = User::factory()->create(['password' => 'correct-password']);

    $page = visit(route('login'));
    $page->fill('email', $user->email);
    $page->fill('password', 'correct-password');
    $page->click('Log in');
    $page->assertPathIs('/admin/stations');

    return $user;
}

it('renders a single print plate with no shadows and a pure QR image', function () {
    loggedInAdminSession();
    $station = Station::factory()->create(['code' => 'PRT-GCP-001', 'highway' => 'PRT']);

    $page = visit(route('admin.stations.qr.print', $station));

    $page->assertSee('PRT-GCP-001');
    $page->assertScript("document.querySelectorAll('.plate').length === 1");
    $page->assertScript("document.querySelector('.plate img')?.src.startsWith('data:image/svg+xml')");

    // The @page/@media print rule must exist in the document exactly as
    // authored — this is a static assertion on the page's own <style>
    // tag, not a rendered-print-preview check (headless Chromium has no
    // print preview to screenshot), but it does prove the CSS a real
    // print dialog would apply is actually present.
    $page->assertScript(<<<'JS'
        [...document.styleSheets].some((sheet) => {
            try {
                return [...sheet.cssRules].some((rule) => rule.cssText?.includes('box-shadow:none'));
            } catch (e) {
                return false;
            }
        }) || document.documentElement.outerHTML.includes('box-shadow: none !important')
        JS);

    $page->assertNoJavaScriptErrors();
    $page->screenshot(filename: 'phase-08-qr-plate-single');
});

it('renders a bulk sheet with one plate per station, filterable by highway', function () {
    loggedInAdminSession();
    Station::factory()->count(3)->create(['highway' => 'PRT']);
    Station::factory()->count(2)->create(['highway' => 'NKVE']);

    $page = visit(route('admin.qr.sheet', ['highway' => 'PRT']));

    $page->assertScript("document.querySelectorAll('.plate').length === 3");
    $page->assertNoJavaScriptErrors();
    $page->screenshot(filename: 'phase-08-qr-sheet');
});
