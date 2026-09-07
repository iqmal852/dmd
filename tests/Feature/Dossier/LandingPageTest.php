<?php

declare(strict_types=1);

namespace Tests\Feature\Dossier;

use Tests\TestCase;

/**
 * plan/phases/phase-03-dossier-shell.md M3.7 — reproduces Picture1.png
 * panels 5 (How It Works) and 6 (Key Benefits). Inertia renders content
 * client-side, so a Feature test can only assert the component/props
 * resolve correctly; the on-screen text is asserted in the browser test
 * (tests/Browser/LandingPageTest.php).
 */
class LandingPageTest extends TestCase
{
    public function test_it_renders_the_welcome_component(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('welcome'));
    }

    public function test_it_does_not_require_authentication(): void
    {
        $this->get('/')->assertOk();
    }
}
