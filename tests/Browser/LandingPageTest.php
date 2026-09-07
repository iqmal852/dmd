<?php

declare(strict_types=1);

/**
 * plan/phases/phase-03-dossier-shell.md M3.7 — Picture1.png panels 5 & 6,
 * reproduced verbatim.
 */
it('shows every how-it-works step and key benefit', function () {
    $page = visit('/');

    $page->assertSee('SCAN')
        ->assertSee('QR Code')
        ->assertSee('CONNECT')
        ->assertSee('ACCESS')
        ->assertSee('VIEW')
        ->assertSee('DOWNLOAD')
        ->assertSee('Faster')
        ->assertSee('Accurate')
        ->assertSee('Secure')
        ->assertSee('Always')
        ->assertSee('Supports')
        ->assertNoJavaScriptErrors();
});

it('has no horizontal overflow at 390px', function () {
    visit('/')
        ->resize(390, 844)
        ->assertScript('document.body.scrollWidth <= window.innerWidth + 1');
});
